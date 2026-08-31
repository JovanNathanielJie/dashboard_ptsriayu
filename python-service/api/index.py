from fastapi import FastAPI, UploadFile, File, Form, HTTPException
import pandas as pd
import openpyxl
from datetime import datetime
from mlxtend.preprocessing import TransactionEncoder
from mlxtend.frequent_patterns import apriori, association_rules
import io

app = FastAPI(title="Apriori Service - PT Sriayu Citra Mandiri")

@app.get("/")
def health_check():
    return {"status": "ok", "service": "apriori-service"}

@app.post("/parse-excel")
async def parse_excel(
    file: UploadFile = File(...),
    periode_awal: str = Form(...),
    periode_akhir: str = Form(...),
):
    # 1. Baca file Excel mentah (format ekspor Accurate Online, blok berulang per faktur)
    try:
        contents = await file.read()
        wb = openpyxl.load_workbook(io.BytesIO(contents), data_only=True)
        try:
            sheet = wb["Rincian Faktur Penjualan"]
        except KeyError:
            sheet = wb.active
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Gagal membaca file Excel: {str(e)}")

    # 2. Parsing baris per baris -- state machine (SAMA PERSIS seperti notebook tervalidasi)
    parsed_data = []
    current_faktur = None
    current_tanggal = None
    found_nomor_faktur = False

    for row in sheet.iter_rows(values_only=True):
        if len(row) > 6 and row[3] == "Nomor #":
            current_faktur = str(row[6]).strip() if row[6] else None
            found_nomor_faktur = True
            continue
        if len(row) > 6 and row[3] == "Tanggal":
            val_tgl = row[6]
            if isinstance(val_tgl, datetime):
                current_tanggal = val_tgl
            elif val_tgl:
                current_tanggal = pd.to_datetime(str(val_tgl), errors="coerce")
            continue
        if len(row) > 11 and current_faktur is not None:
            kode_item = row[2]
            nama_item = row[7]
            qty = row[11]
            if kode_item is not None and nama_item is not None and isinstance(qty, (int, float)):
                parsed_data.append({
                    "nomor_faktur": current_faktur,
                    "tanggal": current_tanggal,
                    "nama_barang": str(nama_item).strip(),
                })

    if not found_nomor_faktur:
        raise HTTPException(
            status_code=422,
            detail="File yang diunggah bukan format ekspor Accurate Online yang valid. Label 'Nomor #' tidak ditemukan.",
        )

    df_raw = pd.DataFrame(parsed_data)
    if df_raw.empty:
        raise HTTPException(status_code=422, detail="Tidak ada item transaksi yang berhasil diproses dari file.")

    # 3. Data Selection -- filter periode tanggal
    df_raw["tanggal"] = pd.to_datetime(df_raw["tanggal"], errors="coerce")
    mask = (df_raw["tanggal"] >= periode_awal) & (df_raw["tanggal"] <= periode_akhir)
    df_selected = df_raw[mask].copy()

    if df_selected.empty:
        raise HTTPException(
            status_code=422,
            detail=f"Tidak ditemukan transaksi pada rentang tanggal {periode_awal} sampai {periode_akhir} di dalam file yang diunggah.",
        )

    total_baris_raw = len(df_selected)

    # 4. Data Cleansing (SAMA PERSIS seperti notebook tervalidasi)
    df_clean = df_selected.dropna(subset=["nomor_faktur", "nama_barang"])
    df_clean = df_clean[df_clean["nama_barang"].str.len() > 0]
    df_clean = df_clean.drop_duplicates()

    total_baris_clean = len(df_clean)
    total_faktur_unik = int(df_clean["nomor_faktur"].nunique())
    total_produk_unik = int(df_clean["nama_barang"].nunique())

    # 5. Siapkan output JSON
    df_clean = df_clean.copy()
    df_clean["tanggal"] = df_clean["tanggal"].dt.strftime("%Y-%m-%d")
    items = df_clean[["nomor_faktur", "tanggal", "nama_barang"]].to_dict(orient="records")

    return {
        "summary": {
            "total_baris_raw": total_baris_raw,
            "total_baris_clean": total_baris_clean,
            "total_faktur_unik": total_faktur_unik,
            "total_produk_unik": total_produk_unik,
            "baris_duplikat_dihapus": total_baris_raw - total_baris_clean,
        },
        "items": items,
    }

@app.post("/analyze")
async def analyze(payload: dict):
    """
    Body JSON yang diharapkan:
    {
        "items": [{"nomor_faktur": "...", "nama_barang": "..."}, ...],
        "min_support": 0.10,
        "max_len": 2,
        "min_confidence": 0.60
    }
    """
    items = payload.get("items", [])
    min_support = float(payload.get("min_support", 0.10))
    max_len = int(payload.get("max_len", 2))
    min_confidence = float(payload.get("min_confidence", 0.60))

    if not items:
        raise HTTPException(status_code=422, detail="Data items kosong, tidak dapat dianalisis.")

    df = pd.DataFrame(items)
    basket = df.groupby("nomor_faktur")["nama_barang"].apply(list).tolist()

    te = TransactionEncoder()
    te_ary = te.fit(basket).transform(basket)
    df_onehot = pd.DataFrame(te_ary, columns=te.columns_)

    frequent_itemsets = apriori(df_onehot, min_support=min_support, use_colnames=True, max_len=max_len)
    if frequent_itemsets.empty:
        raise HTTPException(
            status_code=422,
            detail="Tidak ditemukan frequent itemset dengan parameter yang diberikan. Coba turunkan min_support.",
        )

    frequent_itemsets = frequent_itemsets.copy()
    frequent_itemsets["length"] = frequent_itemsets["itemsets"].apply(len)

    frequent_itemsets_out = [
        {
            "itemset": ", ".join(sorted(list(row["itemsets"]))),
            "length": int(row["length"]),
            "support": float(row["support"]),
        }
        for _, row in frequent_itemsets.iterrows()
    ]

    rules = association_rules(frequent_itemsets, metric="confidence", min_threshold=min_confidence)
    rules_out = []
    for _, row in rules.iterrows():
        rules_out.append({
            "antecedent": ", ".join(list(row["antecedents"])),
            "consequent": ", ".join(list(row["consequents"])),
            "support": float(row["support"]),
            "confidence": float(row["confidence"]),
            "lift": float(row["lift"]),
        })

    return {
        "total_frequent_itemsets": len(frequent_itemsets_out),
        "total_association_rules": len(rules_out),
        "frequent_itemsets": frequent_itemsets_out,
        "association_rules": rules_out,
    }
