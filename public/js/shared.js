/**
 * SILAP – Sistem Informasi Laporan PPSU DKI Jakarta
 * Shared Utilities, Data Store, GPS Watermark Generator & Session
 */

const KATEGORI_INFO = {
  jalan: {
    label: "Prasarana Jalan",
    color: "orange",
    tags: ["Jalan rusak (ringan)", "Trotoar", "Aspal", "Marka", "Paving Block", "Kanstin"]
  },
  taman: {
    label: "Taman & RTH",
    color: "green",
    tags: ["Pemangkasan pohon rendah", "Taman Kota", "Rumput", "RTH", "Perdu", "Penghijauan"]
  },
  saluran: {
    label: "Saluran Air",
    color: "blue",
    tags: ["Drainase", "Got", "Irigasi", "Kuras Lumpur", "Gorong-gorong"]
  },
  kebersihan: {
    label: "Kebersihan",
    color: "yellow",
    tags: ["Sapuan", "TPS", "Sampah", "Gerobak Motor", "Limbah"]
  },
  kantor: {
    label: "Sarana Kantor",
    color: "purple",
    tags: ["Fasilitas Kantor", "Logistik", "Prasarana Kelurahan", "Inventaris"]
  }
};

if (typeof window !== "undefined") {
  window.KATEGORI_INFO = KATEGORI_INFO;
}

// Sync KATEGORI_INFO with localStorage dynamic categories if available
try {
  const customKat = localStorage.getItem("silap_kategori_db");
  if (customKat) {
    const parsed = JSON.parse(customKat);
    if (parsed && typeof parsed === "object") {
      Object.assign(KATEGORI_INFO, parsed);
    }
  }
} catch(e) {}

function getKategoriDB() {
  try {
    const raw = localStorage.getItem("silap_kategori_db");
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed && typeof parsed === "object" && Object.keys(parsed).length > 0) {
        return parsed;
      }
    }
  } catch(e) {}
  return JSON.parse(JSON.stringify(KATEGORI_INFO));
}

function saveKategoriDB(db) {
  try {
    localStorage.setItem("silap_kategori_db", JSON.stringify(db));
  } catch(e) {}
  if (typeof KATEGORI_INFO !== "undefined") {
    Object.keys(db).forEach(k => {
      KATEGORI_INFO[k] = db[k];
    });
  }
}

const DAFTAR_RW = [
  "RW 01", "RW 02", "RW 03", "RW 04", "RW 05",
  "RW 06", "RW 07", "RW 08", "RW 09", "RW 10"
];

const DAFTAR_RW_JALAN = {
  "RW 01": "Jl. Lapan",
  "RW 02": "Jl. Gandaria",
  "RW 03": "Jl. Gandaria",
  "RW 04": "Jl. Buah",
  "RW 05": "Jl. Raya Bogor",
  "RW 06": "Jl. Belly",
  "RW 07": "Jl. Tipar",
  "RW 08": "Jl. Sukamto",
  "RW 09": "Jl. Haji Balok",
  "RW 10": "Jl. Lewa"
};

const DAFTAR_REGU_PPSU = [
  "Regu 1", "Regu 2", "Regu 3", "Regu 4", "Regu 5",
  "Regu 6", "Regu 7", "Regu 8", "Regu 9", "Regu 10"
];

function getKategoriIconSvg(kat) {
  switch(kat) {
    case "jalan":
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19L9 5"></path><path d="M20 19L15 5"></path><line x1="12" y1="7" x2="12" y2="9"></line><line x1="12" y1="13" x2="12" y2="15"></line></svg>';
    case "saluran":
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12c1.5-1.5 3.5-1.5 5 0s3.5 1.5 5 0 3.5-1.5 5 0 3.5-1.5 5 0"></path><path d="M2 17c1.5-1.5 3.5-1.5 5 0s3.5 1.5 5 0 3.5-1.5 5 0 3.5-1.5 5 0"></path></svg>';
    case "taman":
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-7"></path><path d="M17 14c2.2 0 4-1.8 4-4 0-1.8-1.2-3.3-2.8-3.8C18 3.9 15.3 2 12 2S6 3.9 5.8 6.2C4.2 6.7 3 8.2 3 10c0 2.2 1.8 4 4 4"></path></svg>';
    case "kebersihan":
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M19 21v-4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v4"></path><path d="M12 3v12"></path></svg>';
    case "kantor":
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="22" x2="9" y2="22.01"></line><line x1="15" y1="22" x2="15" y2="22.01"></line><line x1="8" y1="6" x2="10" y2="6"></line><line x1="14" y1="6" x2="16" y2="6"></line><line x1="8" y1="10" x2="10" y2="10"></line><line x1="14" y1="10" x2="16" y2="10"></line><line x1="8" y1="14" x2="10" y2="14"></line><line x1="14" y1="14" x2="16" y2="14"></line></svg>';
    default:
      return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
  }
}

const USERS = {
  admin: { username: "admin", password: "admin123", role: "admin", name: "Siti Aminah", wilayah: "jaktim" },
  ketua: { username: "ketua", password: "ketua123", role: "ketua", name: "Budi Santoso", wilayah: "jaktim" },
};

/* Helper Watermark Photo Generator (Canvas) */
function generateFieldPhoto(tahap, katTitle, alamat, lat, lng, officerName) {
  if (lat === undefined || isNaN(lat)) lat = -6.338520;
  if (lng === undefined || isNaN(lng)) lng = 106.865410;
  if (!officerName) officerName = "Petugas Regu Lapangan PPSU Pekayon";

  const c = document.createElement("canvas");
  c.width = 640;
  c.height = 420;
  const g = c.getContext("2d");

  // Sky & Ambience Gradient
  const sky = g.createLinearGradient(0, 0, 0, 240);
  if (katTitle.indexOf("Taman") !== -1) {
    sky.addColorStop(0, "#38bdf8");
    sky.addColorStop(1, "#bbf7d0");
  } else if (katTitle.indexOf("Saluran") !== -1) {
    sky.addColorStop(0, "#475569");
    sky.addColorStop(1, "#94a3b8");
  } else if (katTitle.indexOf("PJU") !== -1) {
    sky.addColorStop(0, "#0f172a");
    sky.addColorStop(1, "#1e293b");
  } else {
    sky.addColorStop(0, "#fb923c");
    sky.addColorStop(1, "#fed7aa");
  }
  g.fillStyle = sky;
  g.fillRect(0, 0, 640, 240);

  // Urban Silhouette
  g.fillStyle = "rgba(30, 41, 59, 0.35)";
  for (let i = 0; i < 9; i++) {
    g.fillRect(i * 75, 110 - (i % 3) * 22, 60, 130);
  }

  // Ground Surface
  g.fillStyle = katTitle.indexOf("Taman") !== -1 ? "#166534" : "#334155";
  g.fillRect(0, 240, 640, 180);

  // PPSU Kanstin Curb
  g.fillStyle = "#f97316";
  g.fillRect(0, 240, 640, 12);
  g.fillStyle = "#facc15";
  g.fillRect(0, 252, 640, 4);

  // Stage Illustration
  if (tahap === 0) {
    // 0%: Kerusakan Lapangan
    g.fillStyle = "#78350f";
    for (let i = 0; i < 24; i++) {
      g.fillRect(60 + (i * 22) % 520, 270 + (i * 17) % 80, 24, 12);
    }
    g.strokeStyle = "#ef4444";
    g.lineWidth = 4;
    g.strokeRect(40, 260, 220, 90);
  } else if (tahap === 50) {
    // 50%: Petugas Oranye Bekerja
    g.fillStyle = "#ea580c";
    g.fillRect(300, 220, 40, 55);
    g.fillStyle = "#facc15";
    g.fillRect(300, 242, 40, 8);
    g.fillStyle = "#1e293b";
    g.fillRect(300, 275, 40, 45);
    g.fillStyle = "#fed7aa";
    g.beginPath();
    g.arc(320, 205, 15, 0, Math.PI * 2);
    g.fill();
    g.fillStyle = "#ea580c";
    g.fillRect(306, 192, 28, 8);
    g.strokeStyle = "#94a3b8";
    g.lineWidth = 4;
    g.beginPath();
    g.moveTo(340, 240);
    g.lineTo(375, 305);
    g.stroke();
  } else {
    // 100%: Tuntas Bersih & Terverifikasi
    g.fillStyle = "rgba(255, 255, 255, 0.4)";
    for (let i = 0; i < 6; i++) {
      g.fillRect(60 + i * 95, 290, 60, 6);
    }
    g.fillStyle = "#10b981";
    g.beginPath();
    g.arc(580, 270, 25, 0, Math.PI * 2);
    g.fill();
    g.fillStyle = "#ffffff";
    g.font = "bold 24px sans-serif";
    g.fillText("✓", 572, 278);
  }

  // Render modern GPS Timestamp Watermark matching official surveyor standard
  const stageLabel = tahap === 0 ? "FOTO SEBELUM (0%)" : tahap === 50 ? "PROSES (50%)" : "FOTO SESUDAH (100%)";
  drawTimestampWatermark(g, 640, 420, {
    alamat: alamat,
    lat: lat,
    lng: lng,
    accuracy: 4,
    badge: "PPSU DKI JAKARTA • " + stageLabel,
    officer: officerName
  });

  return c.toDataURL("image/jpeg", 0.9);
}

/**
 * Modern GPS & Timestamp Watermark Renderer
 * Menghasilkan tata letak presisi sesuai format TimeStamp Camera:
 * - Jam besar
 * - Garis pembatas oranye/kuning
 * - Tanggal & Nama Hari (Indonesia)
 * - Alamat jalan, kelurahan/kota, dan koordinat GPS Lat/Lng akurat
 */
function drawTimestampWatermark(ctx, width, height, opts = {}) {
  const now = opts.now || new Date();
  const DAYS_ID = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];

  const timeStr = opts.time || (String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0"));
  const dayStr = opts.day || DAYS_ID[now.getDay()];
  const dateStr = opts.date || (String(now.getDate()).padStart(2, "0") + "/" + String(now.getMonth() + 1).padStart(2, "0") + "/" + now.getFullYear());

  const latNum = opts.lat !== undefined && !isNaN(Number(opts.lat)) ? Number(opts.lat) : -6.338520;
  const lngNum = opts.lng !== undefined && !isNaN(Number(opts.lng)) ? Number(opts.lng) : 106.865410;
  const latStr = (latNum >= 0 ? "+" : "") + latNum.toFixed(6);
  const lngStr = (lngNum >= 0 ? "+" : "") + lngNum.toFixed(6);
  const acc = opts.accuracy || 4;

  let line1 = opts.addressLine1;
  let line2 = opts.addressLine2;
  let line3 = opts.addressLine3 || `GPS: Lat ${latStr}, Long ${lngStr} (±${acc}m)`;

  if (!line1 && !line2) {
    const raw = opts.alamat || "Jl. Raya Kalisari No. 12, RW 01, Kel. Pekayon, Jakarta Timur";
    const parts = raw.split(",");
    if (parts.length >= 2) {
      line1 = parts[0].trim() + ",";
      line2 = parts.slice(1).join(", ").trim();
    } else {
      line1 = raw;
      line2 = "Pekayon, Pasar Rebo, Jakarta Timur, DKI Jakarta";
    }
  }

  // Skala dinamis berdasarkan lebar foto
  const s = Math.max(0.65, Math.min(2.5, width / 640));

  ctx.save();

  // Gradient halus di bawah agar teks sangat kontras dan terbaca di segala background
  const gradH = Math.round(220 * s);
  const grad = ctx.createLinearGradient(0, height - gradH, 0, height);
  grad.addColorStop(0, "rgba(0, 0, 0, 0.0)");
  grad.addColorStop(0.25, "rgba(0, 0, 0, 0.35)");
  grad.addColorStop(0.65, "rgba(0, 0, 0, 0.70)");
  grad.addColorStop(1, "rgba(0, 0, 0, 0.90)");
  ctx.fillStyle = grad;
  ctx.fillRect(0, height - gradH, width, gradH);

  // Soft shadow
  ctx.shadowColor = "rgba(0, 0, 0, 0.95)";
  ctx.shadowBlur = Math.round(5 * s);
  ctx.shadowOffsetX = 0;
  ctx.shadowOffsetY = Math.round(1.5 * s);

  const leftPad = Math.round(24 * s);
  const bottomPad = Math.round(22 * s);

  const fontCoords = `500 ${Math.round(13.5 * s)}px 'Plus Jakarta Sans', Arial, sans-serif`;
  const fontAddr = `600 ${Math.round(15.5 * s)}px 'Plus Jakarta Sans', Arial, sans-serif`;
  const lineSpacing = Math.round(21 * s);

  const yLine3 = height - bottomPad;
  const yLine2 = yLine3 - lineSpacing;
  const yLine1 = yLine2 - lineSpacing;

  // Render Alamat & Koordinat
  ctx.fillStyle = "#E2E8F0";
  ctx.font = fontCoords;
  ctx.fillText(line3, leftPad, yLine3);

  ctx.fillStyle = "#FFFFFF";
  ctx.font = fontAddr;
  if (line2) ctx.fillText(line2, leftPad, yLine2);
  ctx.fillText(line1, leftPad, yLine1);

  // Bagian Waktu (Jam besar)
  const timeFont = `700 ${Math.round(52 * s)}px 'Plus Jakarta Sans', Arial, sans-serif`;
  const dateFont = `700 ${Math.round(18 * s)}px 'Plus Jakarta Sans', Arial, sans-serif`;
  const dayFont = `600 ${Math.round(17 * s)}px 'Plus Jakarta Sans', Arial, sans-serif`;

  const timeBaseline = yLine1 - Math.round(16 * s);

  ctx.font = timeFont;
  ctx.fillStyle = "#FFFFFF";
  ctx.fillText(timeStr, leftPad, timeBaseline);

  const timeWidth = ctx.measureText(timeStr).width;

  // Garis vertikal pembatas warna kuning/oranye
  const dividerX = leftPad + timeWidth + Math.round(12 * s);
  const dividerW = Math.max(3, Math.round(3.5 * s));
  const dividerTop = timeBaseline - Math.round(41 * s);
  const dividerBottom = timeBaseline + Math.round(2 * s);

  ctx.strokeStyle = "#F59E0B";
  ctx.lineWidth = dividerW;
  ctx.lineCap = "round";
  ctx.beginPath();
  ctx.moveTo(dividerX, dividerTop);
  ctx.lineTo(dividerX, dividerBottom);
  ctx.stroke();

  // Tanggal dan Hari di samping garis pembatas
  const textX = dividerX + Math.round(12 * s);
  const dateY = dividerTop + Math.round(16 * s);
  const dayY = dividerBottom - Math.round(2 * s);

  ctx.font = dateFont;
  ctx.fillStyle = "#FFFFFF";
  ctx.fillText(dateStr, textX, dateY);

  ctx.font = dayFont;
  ctx.fillText(dayStr, textX, dayY);

  // Badge identitas resmi
  if (opts.badge) {
    const badgeFont = `700 ${Math.round(11 * s)}px 'Plus Jakarta Sans', Arial, sans-serif`;
    ctx.font = badgeFont;
    const badgeW = ctx.measureText(opts.badge).width + Math.round(16 * s);
    const badgeH = Math.round(22 * s);
    const badgeX = leftPad;
    const badgeY = dividerTop - Math.round(28 * s);

    ctx.fillStyle = "rgba(249, 115, 22, 0.95)";
    if (ctx.roundRect) {
      ctx.beginPath();
      ctx.roundRect(badgeX, badgeY, badgeW, badgeH, 4);
      ctx.fill();
    } else {
      ctx.fillRect(badgeX, badgeY, badgeW, badgeH);
    }

    ctx.fillStyle = "#FFFFFF";
    ctx.fillText(opts.badge, badgeX + Math.round(8 * s), badgeY + Math.round(15 * s));
  }

  ctx.restore();
}

/**
 * Tempelkan Watermark TimeStamp pada elemen gambar/video dan kembalikan Base64 DataURL
 */
function applyWatermarkToImage(sourceElement, opts = {}) {
  const canvas = document.createElement("canvas");
  const rawW = sourceElement.videoWidth || sourceElement.naturalWidth || sourceElement.width || 1280;
  const rawH = sourceElement.videoHeight || sourceElement.naturalHeight || sourceElement.height || 720;

  // Batasi resolusi maksimal 960px agar memori ringan & muat aman di storage mobile
  const maxDim = 960;
  let w = rawW;
  let h = rawH;
  if (w > maxDim || h > maxDim) {
    if (w > h) {
      h = Math.round((h * maxDim) / w);
      w = maxDim;
    } else {
      w = Math.round((w * maxDim) / h);
      h = maxDim;
    }
  }

  canvas.width = w;
  canvas.height = h;

  const ctx = canvas.getContext("2d");
  // Flip horizontally for front camera (user facing) so captured photo is NOT mirrored
  if (opts.flipHorizontal) {
    ctx.save();
    ctx.scale(-1, 1);
    ctx.drawImage(sourceElement, -w, 0, w, h);
    ctx.restore();
  } else {
    ctx.drawImage(sourceElement, 0, 0, w, h);
  }
  drawTimestampWatermark(ctx, w, h, opts);

  return canvas.toDataURL("image/jpeg", 0.80);
}

/**
 * Mendapatkan URL foto yang aman & valid (mencegah error gambar rusak / broken image)
 */
function getReportPhotoUrl(photoObj, report, stageIndex = 0) {
  if (photoObj && photoObj.src && typeof photoObj.src === "string" && photoObj.src.trim() !== "" && photoObj.src !== "null" && photoObj.src !== "undefined") {
    return photoObj.src;
  }
  if (typeof generateFieldPhoto === "function" && report) {
    try {
      const tahapVal = stageIndex === 0 ? 0 : stageIndex === 1 ? (report.progres || 50) : 100;
      const katTitle = report.jenisPekerjaan || report.judul || "Penanganan Prasarana";
      const alamat = report.alamat || "Kelurahan Pekayon, Pasar Rebo, Jakarta Timur";
      const officer = (report.petugas || "Petugas PPSU") + " (" + (report.regu || "Regu Lapangan") + ")";
      return generateFieldPhoto(tahapVal, katTitle, alamat, report.gpsLat || -6.338520, report.gpsLng || 106.865410, officer);
    } catch(e) {
      console.warn("generateFieldPhoto error:", e);
    }
  }
  return "foto/silap.png";
}

// Master Data 10 RW & 10 Regu PPSU Sesuai 5 Standar Tugas
// Master Data 11 RW & 10 Regu PPSU Kelurahan Pekayon, Pasar Rebo, Jakarta Timur
const INITIAL_REPORTS = [
  {
    id: "PKJ-202610-001",
    noPekerjaan: "PKJ-202610-001",
    minggu: "Laporan Senin (12 Okt 2026) • Tugas 1",
    rw: "RW 01",
    kat: "kebersihan",
    tugasKe: "Tugas 1",
    jenisPekerjaan: "Pembersihan Jalan & Trotoar",
    judul: "Tugas 1: Pembersihan Jalan & Trotoar - Jl. Lapan RW 01",
    deskripsi: "Pembersihan sampah kering, debu, dedaunan gugur, dan kotoran trotoar serta bahu jalan.",
    alamat: "Jl. Lapan, RW 01, Kel. Pekayon",
    lokasiDetail: "Depan pos linmas RW 01 Jl. Lapan",
    kendala: "Lalu lintas padat pagi hari, petugas menyapu menggunakan rompi reflektif.",
    petugas: "Ahmad Suryadi",
    regu: "Regu 1",
    noHp: "0812-8899-0011",
    jumlahPetugas: 4,
    status: "selesai",
    progres: 100,
    tanggal: "12 Okt 2026, 08:30",
    gpsLat: -6.335500,
    gpsLng: 106.864200,
    foto: []
  },
  {
    id: "PKJ-202610-002",
    noPekerjaan: "PKJ-202610-002",
    minggu: "Laporan Senin (12 Okt 2026) • Tugas 2",
    rw: "RW 02",
    kat: "saluran",
    tugasKe: "Tugas 2",
    jenisPekerjaan: "Pembersihan Saluran Air & Got",
    judul: "Tugas 2: Pembersihan Saluran Air & Got - Jl. Gandaria RW 02",
    deskripsi: "Pembersihan tanaman liar, rumput pengganggu, serta pengerukan endapan lumpur dan sampah selokan.",
    alamat: "Jl. Gandaria, RW 02, Kel. Pekayon",
    lokasiDetail: "Sepanjang saluran got Jl. Gandaria samping pos RW 02",
    kendala: "Endapan lumpur cukup padat, pengerukan dilakukan bertahap.",
    petugas: "Slamet Riyadi",
    regu: "Regu 2",
    noHp: "0817-9988-1122",
    jumlahPetugas: 4,
    status: "selesai",
    progres: 100,
    tanggal: "12 Okt 2026, 10:15",
    gpsLat: -6.337100,
    gpsLng: 106.862500,
    foto: []
  },
  {
    id: "PKJ-202610-003",
    noPekerjaan: "PKJ-202610-003",
    minggu: "Laporan Selasa (13 Okt 2026) • Tugas 3",
    rw: "RW 03",
    kat: "taman",
    tugasKe: "Tugas 3",
    jenisPekerjaan: "Perawatan Taman & Jalur Hijau",
    judul: "Tugas 3: Perawatan Taman & Jalur Hijau - Jl. Gandaria RW 03",
    deskripsi: "Pemeliharaan tanaman hias, pembersihan area hijau lingkungan, dan penataan pot tanaman.",
    alamat: "Jl. Gandaria, RW 03, Kel. Pekayon",
    lokasiDetail: "Area RTH di sebelah balai warga RW 03 Gandaria",
    kendala: "Cuaca sempat gerimis, pekerjaan dilanjutkan setelah permukaan rumput kering.",
    petugas: "Gunawan Marzuki",
    regu: "Regu 3",
    noHp: "0816-7788-9900",
    jumlahPetugas: 4,
    status: "proses",
    progres: 60,
    tanggal: "13 Okt 2026, 09:00",
    gpsLat: -6.339200,
    gpsLng: 106.866500,
    foto: []
  },
  {
    id: "PKJ-202610-004",
    noPekerjaan: "PKJ-202610-004",
    minggu: "Laporan Rabu (14 Okt 2026) • Tugas 4",
    rw: "RW 04",
    kat: "taman",
    tugasKe: "Tugas 4",
    jenisPekerjaan: "Pemangkasan Pohon & Dahan Rendah",
    judul: "Tugas 4: Pemangkasan Pohon & Dahan Rendah - Jl. Buah RW 04",
    deskripsi: "Pemotongan ranting/dahan pohon (tinggi maksimal 2 meter) yang menghalangi jalan atau kabel utilitas.",
    alamat: "Jl. Buah, RW 04, Kel. Pekayon",
    lokasiDetail: "Sisi kiri jalan depan pos RW 04 Buah",
    kendala: "Kabel utilitas melintas di sela dahan, pemangkasan manual ekstra hati-hati.",
    petugas: "Suroto Wibowo",
    regu: "Regu 4",
    noHp: "0813-7722-1133",
    jumlahPetugas: 4,
    status: "proses",
    progres: 50,
    tanggal: "14 Okt 2026, 11:20",
    gpsLat: -6.341500,
    gpsLng: 106.863800,
    foto: []
  },
  {
    id: "PKJ-202610-005",
    noPekerjaan: "PKJ-202610-005",
    minggu: "Laporan Kamis (15 Okt 2026) • Tugas 5",
    rw: "RW 05",
    kat: "jalan",
    tugasKe: "Tugas 5",
    jenisPekerjaan: "Perbaikan Jalan & Trotoar Ringan",
    judul: "Tugas 5: Perbaikan Jalan & Trotoar Ringan - Jl. Raya Bogor RW 05",
    deskripsi: "Penambalan lubang jalan skala kecil, perapihan conblock/paving amblas, dan kanstin trotoar lepas.",
    alamat: "Jl. Raya Bogor KM 27, RW 05, Kel. Pekayon",
    lokasiDetail: "Halaman parkir dan pertigaan jalan lingkungan RW 05",
    kendala: "Pekerjaan dilakukan bertahap agar tidak menghambat kendaraan warga yang melintas.",
    petugas: "Hartono Prasetyo",
    regu: "Regu 5",
    noHp: "0815-4433-2211",
    jumlahPetugas: 4,
    status: "selesai",
    progres: 100,
    tanggal: "15 Okt 2026, 13:40",
    gpsLat: -6.343200,
    gpsLng: 106.868000,
    foto: []
  },
  {
    id: "PKJ-202610-006",
    noPekerjaan: "PKJ-202610-006",
    minggu: "Minggu ke-3 (Okt 2026)",
    rw: "RW 06",
    kat: "taman",
    jenisPekerjaan: "Pemangkasan pohon rendah",
    judul: "Pemangkasan pohon rendah - Jl. Belly RW 06",
    deskripsi: "Pemangkasan ranting pohon akasia yang menjorok rendah membahayakan kendaraan warga.",
    alamat: "Jl. Belly, RW 06, Kel. Pekayon",
    lokasiDetail: "Depan pagar pembatas sekolah RW 06 Belly",
    kendala: "Perlu pengaturan lalu lintas searah selama pengangkutan sisa dahan.",
    petugas: "Suwanto Hadipuro",
    regu: "Regu Taman",
    noHp: "0812-3344-5566",
    jumlahPetugas: 4,
    status: "belum",
    progres: 25,
    tanggal: "19 Okt 2026, 08:00",
    gpsLat: -6.336200,
    gpsLng: 106.867200,
    foto: []
  },
  {
    id: "PKJ-202610-007",
    noPekerjaan: "PKJ-202610-007",
    minggu: "Minggu ke-3 (Okt 2026)",
    rw: "RW 07",
    kat: "jalan",
    jenisPekerjaan: "Jalan rusak (ringan)",
    judul: "Perbaikan Jalan rusak (ringan) - Jl. Tipar RW 07",
    deskripsi: "Pemasangan kembali batu paving trotoar yang lepas agar tidak mencelakai pejalan kaki.",
    alamat: "Jl. Tipar, RW 07, Kel. Pekayon",
    lokasiDetail: "Trotoar jalan samping pos kamling RW 07 Tipar",
    kendala: "Material pasir pasang sempat terlambat datang dari depo gudang.",
    petugas: "Ahmad Suryadi",
    regu: "Regu Jalan",
    noHp: "0812-8899-0011",
    jumlahPetugas: 4,
    status: "belum",
    progres: 0,
    tanggal: "20 Okt 2026, 07:45",
    gpsLat: -6.334100,
    gpsLng: 106.865800,
    foto: []
  },
  {
    id: "PKJ-202610-008",
    noPekerjaan: "PKJ-202610-008",
    minggu: "Minggu ke-4 (Okt 2026)",
    rw: "RW 08",
    kat: "saluran",
    jenisPekerjaan: "Pembersihan Saluran / Got",
    judul: "Kuras Endapan Got Lingkungan - Jl. Sukamto RW 08",
    deskripsi: "Pengangkatan lumpur hitam dan sampah plastik di saluran pemukiman Jl. Sukamto RW 08.",
    alamat: "Jl. Sukamto, RW 08, Kel. Pekayon",
    lokasiDetail: "Saluran terbuka depan pos RW 08 Jl. Sukamto",
    kendala: "Akses jalan sempit gerobak motor tidak bisa masuk, pengangkutan estafet karung lumpur.",
    petugas: "Bambang Sutrisno",
    regu: "Regu Jalan",
    noHp: "0818-5566-7788",
    jumlahPetugas: 6,
    status: "proses",
    progres: 70,
    tanggal: "21 Okt 2026, 09:30",
    gpsLat: -6.338800,
    gpsLng: 106.861500,
    foto: []
  },
  {
    id: "PKJ-202610-009",
    noPekerjaan: "PKJ-202610-009",
    minggu: "Minggu ke-4 (Okt 2026)",
    rw: "RW 09",
    kat: "kebersihan",
    jenisPekerjaan: "Pembersihan Sampah Lingkungan",
    judul: "Pembersihan Sampah Liar & Penyemprotan - Jl. Haji Balok RW 09",
    deskripsi: "Pengangkutan timbulan sampah sisa daun kering dan plastik di Jl. Haji Balok RW 09.",
    alamat: "Jl. Haji Balok, RW 09, Kel. Pekayon",
    lokasiDetail: "Sepanjang jalur jalan Jl. Haji Balok RW 09",
    kendala: "Perlu kewaspadaan tinggi terhadap arus kendaraan bermotor.",
    petugas: "Rudi Hartono",
    regu: "Regu Taman",
    noHp: "0813-1122-3344",
    jumlahPetugas: 4,
    status: "selesai",
    progres: 100,
    tanggal: "22 Okt 2026, 06:30",
    gpsLat: -6.332500,
    gpsLng: 106.864000,
    foto: []
  },
  {
    id: "PKJ-202610-010",
    noPekerjaan: "PKJ-202610-010",
    minggu: "Minggu ke-4 (Okt 2026)",
    rw: "RW 10",
    kat: "kantor",
    jenisPekerjaan: "Pemeliharaan Sarana Kantor",
    judul: "Pembersihan & Penataan Posko Bersama - Jl. Lewa RW 10",
    deskripsi: "Pengecatan dinding posko, pembersihan talang air kantor operasional RW 10.",
    alamat: "Jl. Lewa, RW 10, Kel. Pekayon",
    lokasiDetail: "Gedung serbaguna posko terpadu RW 10 Jl. Lewa",
    kendala: "Peralatan tangga lipat dipinjam bergantian dengan regu lain.",
    petugas: "Hartono Prasetyo",
    regu: "Regu Kantor",
    noHp: "0815-4433-2211",
    jumlahPetugas: 3,
    status: "selesai",
    progres: 100,
    tanggal: "23 Okt 2026, 14:00",
    gpsLat: -6.334800,
    gpsLng: 106.868500,
    foto: []
  }
];

// LocalStorage helpers (versi 8 untuk penugasan Kelurahan Pekayon, Pasar Rebo)
const STORAGE_KEY_REPORTS = "silap_laporan_data_v8";
const STORAGE_KEY_USER = "silap_current_user";
const STORAGE_KEY_ANGGOTA = "silap_anggota_data_v3";
const STORAGE_KEY_REGU = "silap_regu_data_v3";

let _laporanMemoryCache = null;

// Realtime Cross-Tab Broadcast Channel
let _syncChannel = null;
try {
  if (typeof BroadcastChannel !== "undefined") {
    _syncChannel = new BroadcastChannel("silap_sync_channel");
  }
} catch(e) {}

function getLaporanDB() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_REPORTS);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed) && parsed.length > 0) {
        _laporanMemoryCache = parsed;
        return parsed;
      }
    }
  } catch(e) {}

  if (_laporanMemoryCache && Array.isArray(_laporanMemoryCache) && _laporanMemoryCache.length > 0) {
    return _laporanMemoryCache;
  }

  const now = new Date();
  const indoMonths = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  const todayStr = String(now.getDate()).padStart(2, "0") + " " + indoMonths[now.getMonth()] + " " + now.getFullYear() + ", 08:30";
  const yest = new Date(now.getTime() - 24 * 60 * 60 * 1000);
  const yestStr = String(yest.getDate()).padStart(2, "0") + " " + indoMonths[yest.getMonth()] + " " + yest.getFullYear() + ", 10:15";

  const seeded = INITIAL_REPORTS.map((l, idx) => {
    let tgl = l.tanggal;
    if (idx === 0 || idx === 6) tgl = todayStr;
    else if (idx === 1 || idx === 7) tgl = yestStr;
    return Object.assign({}, l, {
      tanggal: tgl,
      foto: [
        { src: "", timestamp: tgl, name: "sebelum_" + l.id + ".jpg", tahap: "Sebelum (0%)" },
        { src: "", timestamp: tgl, name: "proses_" + l.id + ".jpg", tahap: "Proses (" + l.progres + "%)" },
        { src: "", timestamp: tgl, name: "sesudah_" + l.id + ".jpg", tahap: "Sesudah (100%)" }
      ]
    });
  });

  _laporanMemoryCache = seeded;
  saveLaporanDB(seeded);
  return seeded;
}

let _lastLocalSaveTime = 0;
let _isSyncingRealtime = false;

function saveLaporanDB(list) {
  _lastLocalSaveTime = Date.now();
  _laporanMemoryCache = list;
  try {
    localStorage.setItem(STORAGE_KEY_REPORTS, JSON.stringify(list));
  } catch(e) {
    console.warn("Storage quota exceeded, safely pruning dummy mock photos...", e);
    try {
      const cleaned = list.map(item => {
        if (item.id && (String(item.id).startsWith("PKJ-202610-") || String(item.id).startsWith("PKJ-00"))) {
          return {
            ...item,
            foto: (item.foto || []).map(f => ({ ...f, src: "" }))
          };
        }
        return item;
      });
      localStorage.setItem(STORAGE_KEY_REPORTS, JSON.stringify(cleaned));
      _laporanMemoryCache = cleaned;
    } catch(e2) {
      console.error("Critical storage quota error", e2);
    }
  }

  // Notifikasi real-time ke semua tab & iframe aktif
  try {
    window.dispatchEvent(new CustomEvent("silap_laporan_updated", { detail: list }));
    if (_syncChannel) {
      _syncChannel.postMessage({ type: "laporan_updated", timestamp: Date.now() });
    }
  } catch(eEvt) {}

  // Sinkronisasi asinkron ke database MySQL backend (api.php)
  try {
    if (typeof fetch !== "undefined") {
      fetch("api.php?action=save_all_reports", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(list)
      }).catch(err => console.debug("API sync background note:", err));
    }
  } catch(eFetch) {}
}

function formatPhone08(phone) {
  if (!phone) return "";
  let clean = String(phone).trim().replace(/[- ]/g, "");
  if (clean.startsWith("+62")) clean = "0" + clean.substring(3);
  else if (clean.startsWith("62")) clean = "0" + clean.substring(2);
  return clean;
}

// ==========================================================
// DATE & PERIOD FILTER HELPERS (SHARED ACROSS ADMIN & USER)
// ==========================================================
function parseReportDate(lap) {
  if (!lap) return new Date();
  if (lap.isoDate) {
    const p = String(lap.isoDate).split("-");
    if (p.length === 3) return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
  }
  if (lap.tanggal) {
    const indoMo = {
      "jan": 0, "januari": 0,
      "feb": 1, "februari": 1,
      "mar": 2, "maret": 2,
      "apr": 3, "april": 3,
      "mei": 4,
      "jun": 5, "juni": 5,
      "jul": 6, "juli": 6,
      "agu": 7, "agustus": 7, "ags": 7,
      "sep": 8, "september": 8,
      "okt": 9, "oktober": 9,
      "nov": 10, "november": 10,
      "des": 11, "desember": 11
    };
    const m = String(lap.tanggal).match(/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/);
    if (m) {
      const d = parseInt(m[1], 10);
      const mo = indoMo[m[2].toLowerCase()] !== undefined ? indoMo[m[2].toLowerCase()] : 9;
      const y = parseInt(m[3], 10);
      return new Date(y, mo, d);
    }
  }
  return new Date();
}

function getDaysDifference(targetDate) {
  const now = new Date();
  const d1 = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const d2 = new Date(targetDate.getFullYear(), targetDate.getMonth(), targetDate.getDate());
  const diffTime = d1 - d2;
  return Math.floor(diffTime / (1000 * 60 * 60 * 24));
}

function filterReportsByPeriod(reports, period) {
  if (!period || period === "all") return reports;
  return (reports || []).filter(r => {
    const repDate = parseReportDate(r);
    const diff = getDaysDifference(repDate);
    if (period === "today") return diff === 0;
    if (period === "yesterday") return diff === 1;
    if (period === "2days") return diff === 2;
    if (period === "3days") return diff === 3;
    if (period === "7days") return diff >= 0 && diff <= 7;
    if (period === "14days") return diff >= 0 && diff <= 14;
    if (period === "30days") return diff >= 0 && diff <= 30;
    return true;
  });
}

// Master Data: 3 Jenis Regu Sesuai Revisi Klien (Regu Jalan, Regu Taman, Regu Kantor)
const INITIAL_REGU = [
  {
    id: "REG-01",
    nama: "Regu Jalan",
    jenis: "Jalan",
    ketua: "Attar Reyzi",
    noHp: "081906353064",
    wilayah: "10 RW (RW 01 - RW 10)",
    deskripsi: "Penanganan Jalan rusak (ringan), trotoar, marka jalan, kanstin, dan saluran drainase jalan.",
    anggotaIds: ["AGT-002", "AGT-003", "AGT-004", "AGT-011"]
  },
  {
    id: "REG-02",
    nama: "Regu Taman",
    jenis: "Taman",
    ketua: "Slamet Riyadi",
    noHp: "0817-9988-1122",
    wilayah: "10 RW (RW 01 - RW 10)",
    deskripsi: "Pemangkasan pohon rendah, pembersihan taman RTH, pemotongan rumput liar, dan penghijauan.",
    anggotaIds: ["AGT-005", "AGT-006", "AGT-007"]
  },
  {
    id: "REG-03",
    nama: "Regu Kantor",
    jenis: "Kantor",
    ketua: "Hartono Prasetyo",
    noHp: "0815-4433-2211",
    wilayah: "Kantor Kelurahan & 10 Pos RW",
    deskripsi: "Pemeliharaan sarana kantor kelurahan, inventaris logistik regu, pos RW, dan fasilitas umum.",
    anggotaIds: ["AGT-008", "AGT-009", "AGT-010"]
  }
];

function getReguDB() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_REGU);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed) && parsed.length > 0) return parsed;
    }
  } catch(e) {}
  saveReguDB(INITIAL_REGU);
  return INITIAL_REGU;
}

function saveReguDB(list) {
  _lastLocalSaveTime = Date.now();
  try {
    localStorage.setItem(STORAGE_KEY_REGU, JSON.stringify(list));
    window.dispatchEvent(new CustomEvent("silap_regu_updated", { detail: list }));
    if (_syncChannel) _syncChannel.postMessage({ type: "regu_updated", timestamp: Date.now() });
    if (typeof fetch !== "undefined") {
      fetch("api.php?action=save_all_regu", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(list)
      }).catch(err => console.debug("Regu sync note:", err));
    }
  } catch(e) {}
}

// Master Data: Personel PPSU
const INITIAL_ANGGOTA = [
  { id: "AGT-001", nama: "Ahmad Suryadi", role: "Anggota Regu", hp: "081288990011", bidang: "jalan", reguId: "", status: "aktif" },
  { id: "AGT-002", nama: "Budi Santoso", role: "Anggota Regu", hp: "081234567890", bidang: "jalan", reguId: "REG-01", status: "aktif" },
  { id: "AGT-003", nama: "Andi Saputra", role: "Anggota Regu", hp: "081311223344", bidang: "jalan", reguId: "REG-01", status: "aktif" },
  { id: "AGT-004", nama: "Dedi Kurniawan", role: "Anggota Regu", hp: "085722334455", bidang: "jalan", reguId: "REG-01", status: "aktif" },
  { id: "AGT-005", nama: "Slamet Riyadi", role: "Ketua Regu", hp: "081799881122", bidang: "taman", reguId: "REG-02", status: "aktif" },
  { id: "AGT-006", nama: "Suwanto Hadipuro", role: "Anggota Regu", hp: "081233445566", bidang: "taman", reguId: "REG-02", status: "aktif" },
  { id: "AGT-007", nama: "Gunawan Marzuki", role: "Anggota Regu", hp: "081677889900", bidang: "taman", reguId: "REG-02", status: "aktif" },
  { id: "AGT-008", nama: "Hartono Prasetyo", role: "Ketua Regu", hp: "081544332211", bidang: "kantor", reguId: "REG-03", status: "aktif" },
  { id: "AGT-009", nama: "Rudi Hartono", role: "Anggota Regu", hp: "081311223344", bidang: "kantor", reguId: "REG-03", status: "aktif" },
  { id: "AGT-010", nama: "Yusuf Ananda", role: "Anggota Regu", hp: "081566778899", bidang: "kantor", reguId: "REG-03", status: "aktif" },
  { id: "AGT-011", nama: "Attar Reyzi", role: "Ketua Regu", hp: "081906353064", bidang: "jalan", reguId: "REG-01", status: "aktif" },
  { id: "AGT-012", nama: "andini", role: "Anggota Regu", hp: "081314137888", bidang: "jalan", reguId: "", status: "aktif" }
];

function getAnggotaDB() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_ANGGOTA);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed) && parsed.length > 0) {
        let updated = false;
        parsed.forEach(a => {
          if (!a.role) {
            a.role = (a.id === "AGT-011" || a.id === "AGT-005" || a.id === "AGT-008") ? "Ketua Regu" : "Anggota Regu";
            updated = true;
          }
        });
        if (updated) saveAnggotaDB(parsed);
        return parsed;
      }
    }
  } catch(e) {}
  saveAnggotaDB(INITIAL_ANGGOTA);
  return INITIAL_ANGGOTA;
}

function saveAnggotaDB(list) {
  _lastLocalSaveTime = Date.now();
  try {
    localStorage.setItem(STORAGE_KEY_ANGGOTA, JSON.stringify(list));
    window.dispatchEvent(new CustomEvent("silap_anggota_updated", { detail: list }));
    if (_syncChannel) _syncChannel.postMessage({ type: "anggota_updated", timestamp: Date.now() });
    if (typeof fetch !== "undefined") {
      fetch("api.php?action=save_all_anggota", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(list)
      }).catch(err => console.debug("Anggota sync note:", err));
    }
  } catch(e) {}
}

function getCurrentUser() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_USER) || localStorage.getItem("jawara_current_user") || localStorage.getItem("silap_user_session");
    if (raw) return JSON.parse(raw);
  } catch(e) {}
  return USERS.admin;
}

function setCurrentUser(user) {
  try {
    localStorage.setItem(STORAGE_KEY_USER, JSON.stringify(user));
  } catch(e) {}
}

function logoutUser() {
  try {
    localStorage.removeItem(STORAGE_KEY_USER);
    localStorage.removeItem("jawara_current_user");
    localStorage.removeItem("silap_user_session");
    localStorage.removeItem("jawara_user_session");
  } catch(e) {}
  window.location.href = "logout.php";
}

// Modern Toast notification helper
function showToast(message, type) {
  if (!document.getElementById("silap-toast-styles")) {
    const styleEl = document.createElement("style");
    styleEl.id = "silap-toast-styles";
    styleEl.textContent = `
      @keyframes toastSlideIn {
        from { opacity: 0; transform: translateX(22px) scale(0.97); }
        to { opacity: 1; transform: translateX(0) scale(1); }
      }
      @keyframes toastSlideOut {
        from { opacity: 1; transform: translateX(0) scale(1); }
        to { opacity: 0; transform: translateX(22px) scale(0.96); }
      }
      @keyframes toastProgress {
        from { width: 100%; }
        to { width: 0%; }
      }
      #toast-container {
        position: fixed;
        bottom: 24px;
        right: 20px;
        left: auto;
        z-index: 999999;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        pointer-events: none;
        max-width: min(360px, calc(100vw - 24px));
      }
      .silap-toast-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.14), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
        padding: 11px 13px;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 240px;
        max-width: 360px;
        width: fit-content;
        position: relative;
        overflow: hidden;
        pointer-events: auto;
        cursor: pointer;
        font-family: 'Poppins', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        animation: toastSlideIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .silap-toast-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.16), 0 6px 14px -2px rgba(15, 23, 42, 0.06);
      }
      .silap-toast-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .silap-toast-title {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 1px;
      }
      .silap-toast-msg {
        font-size: 0.83rem;
        font-weight: 600;
        color: #0F172A;
        line-height: 1.32;
        word-break: break-word;
      }
      @media (max-width: 640px) {
        #toast-container {
          top: calc(env(safe-area-inset-top, 0px) + 64px);
          bottom: auto;
          right: 8px;
          left: auto;
          max-width: min(255px, 68vw);
          gap: 6px;
        }
        .silap-toast-card {
          min-width: 0;
          max-width: 100%;
          width: 100%;
          padding: 8px 10px;
          gap: 8px;
          border-radius: 12px;
        }
        .silap-toast-icon {
          width: 26px;
          height: 26px;
          border-radius: 7px;
        }
        .silap-toast-icon svg {
          width: 14px;
          height: 14px;
        }
        .silap-toast-title {
          font-size: 0.62rem;
          letter-spacing: 0.3px;
        }
        .silap-toast-msg {
          font-size: 0.74rem;
          line-height: 1.28;
        }
      }
    `;
    document.head.appendChild(styleEl);
  }

  let toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    document.body.appendChild(toastContainer);
  }

  const maxToasts = window.innerWidth <= 640 ? 2 : 3;
  while (toastContainer.children.length >= maxToasts && toastContainer.firstElementChild) {
    toastContainer.firstElementChild.remove();
  }

  const toastType = (type || "info").toLowerCase();
  const typeMap = {
    success: {
      title: "Berhasil",
      color: "#10B981",
      bg: "#ECFDF5",
      icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>`
    },
    error: {
      title: "Perhatian",
      color: "#EF4444",
      bg: "#FEF2F2",
      icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`
    },
    warning: {
      title: "Peringatan",
      color: "#F59E0B",
      bg: "#FFFBEB",
      icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`
    },
    info: {
      title: "Pemberitahuan",
      color: "#EA580C",
      bg: "#FFF7ED",
      icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EA580C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`
    }
  };

  const cfg = typeMap[toastType] || typeMap.info;
  const toast = document.createElement("div");
  toast.className = "silap-toast-card";
  toast.innerHTML = `
    <div class="silap-toast-icon" style="background:${cfg.bg};">
      ${cfg.icon}
    </div>
    <div style="flex:1;min-width:0;">
      <div class="silap-toast-title" style="color:${cfg.color};">
        ${cfg.title}
      </div>
      <div class="silap-toast-msg">
        ${message}
      </div>
    </div>
    <button type="button" class="btn-close-toast" style="background:none;border:none;padding:2px;cursor:pointer;color:#94A3B8;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:color 0.15s;flex-shrink:0;" title="Tutup">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div style="position:absolute;bottom:0;left:0;height:3px;background:${cfg.color};animation:toastProgress 3.5s linear forwards;"></div>
  `;

  toastContainer.appendChild(toast);

  let dismissed = false;
  const dismiss = () => {
    if (dismissed) return;
    dismissed = true;
    toast.style.animation = "toastSlideOut 0.25s ease forwards";
    setTimeout(() => {
      if (toast.parentNode) toast.remove();
    }, 250);
  };

  const timer = setTimeout(dismiss, 3500);

  toast.addEventListener("click", (e) => {
    clearTimeout(timer);
    dismiss();
  });
}

// ==========================================================
// SINKRONISASI REAL-TIME DATABASE MYSQL <-> LOCALSTORAGE
// Menjamin konsistensi data instan antara Petugas & Admin
// ==========================================================
async function syncRealtimeFromBackend() {
  if (typeof fetch === "undefined") return;
  if (_isSyncingRealtime) return;
  _isSyncingRealtime = true;

  try {
    const res = await fetch("api.php?action=sync_realtime&_t=" + Date.now());
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();
    if (data && data.success) {
      // 1. Sinkronisasi Anggota PPSU & Role
      if (Array.isArray(data.anggota)) {
        data.anggota.forEach(a => { if (a.hp) a.hp = formatPhone08(a.hp); });
        const currentAnggotaStr = localStorage.getItem(STORAGE_KEY_ANGGOTA) || "";
        const newAnggotaStr = JSON.stringify(data.anggota);
        if (currentAnggotaStr !== newAnggotaStr) {
          localStorage.setItem(STORAGE_KEY_ANGGOTA, newAnggotaStr);
          window.dispatchEvent(new CustomEvent("silap_anggota_updated", { detail: data.anggota }));
        }
      }

      // 2. Sinkronisasi Master Regu & Personel Tim
      if (Array.isArray(data.regu)) {
        data.regu.forEach(r => {
          if (r.noHp) r.noHp = formatPhone08(r.noHp);
          if (r.no_hp) r.no_hp = formatPhone08(r.no_hp);
        });
        const currentReguStr = localStorage.getItem(STORAGE_KEY_REGU) || "";
        const newReguStr = JSON.stringify(data.regu);
        if (currentReguStr !== newReguStr) {
          localStorage.setItem(STORAGE_KEY_REGU, newReguStr);
          window.dispatchEvent(new CustomEvent("silap_regu_updated", { detail: data.regu }));
        }
      }

      // 3. Sinkronisasi Laporan (Progres 0% -> 50% -> 100%, Status, Validasi)
      if (Array.isArray(data.reports)) {
        const local = getLaporanDB();
        let changed = false;

        if (local.length !== data.reports.length) {
          changed = true;
        } else {
          for (let i = 0; i < data.reports.length; i++) {
            const s = data.reports[i];
            const l = local.find(x => String(x.id) === String(s.id) || (x.noPekerjaan && s.noPekerjaan && String(x.noPekerjaan) === String(s.noPekerjaan)));
            if (!l || l.progres !== s.progres || l.status !== s.status || l.kendala !== s.kendala || l.judul !== s.judul) {
              changed = true;
              break;
            }
          }
        }

        if (changed) {
          const map = new Map();
          data.reports.forEach(r => {
            map.set(String(r.id), r);
            if (r.noPekerjaan) map.set(String(r.noPekerjaan), r);
          });

          local.forEach(l => {
            const s = map.get(String(l.id)) || (l.noPekerjaan ? map.get(String(l.noPekerjaan)) : null);
            if (!s) {
              if (Date.now() - _lastLocalSaveTime < 15000) {
                map.set(String(l.id), l);
              }
            } else {
              const mergedFoto = (s.foto || []).map((sf, idx) => {
                const lf = l.foto && l.foto[idx];
                return {
                  ...sf,
                  src: (lf && lf.src) ? lf.src : (sf.src || ""),
                  timestamp: (lf && lf.timestamp) ? lf.timestamp : (sf.timestamp || "")
                };
              });

              const lProg = parseInt(l.progres, 10) || 0;
              const sProg = parseInt(s.progres, 10) || 0;
              if (Date.now() - _lastLocalSaveTime < 15000 && lProg > sProg) {
                map.set(String(s.id), { ...s, progres: l.progres, status: l.status, foto: mergedFoto });
              } else {
                map.set(String(s.id), { ...l, ...s, foto: mergedFoto });
              }
            }
          });

          const merged = Array.from(map.values());
          _laporanMemoryCache = merged;
          try { localStorage.setItem(STORAGE_KEY_REPORTS, JSON.stringify(merged)); } catch(e) {}
          window.dispatchEvent(new CustomEvent("silap_laporan_updated", { detail: merged }));
        }
      }
    }
  } catch(err) {
    // Abaikan error koneksi sesaat, loop berikutnya akan coba kembali
  } finally {
    _isSyncingRealtime = false;
  }
}

function initRealtimeSyncEngine() {
  syncRealtimeFromBackend();
  // Polling rutin setiap 2.5 detik untuk sinkronisasi multi-device / multi-browser
  if (!window._silapRealtimeTimer) {
    window._silapRealtimeTimer = setInterval(syncRealtimeFromBackend, 2500);
  }
  window.addEventListener("focus", syncRealtimeFromBackend);
  window.addEventListener("online", syncRealtimeFromBackend);
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRealtimeSyncEngine);
  } else {
    initRealtimeSyncEngine();
  }
}

// ==========================================================
// CUSTOM APP DIALOG MODAL (Pengganti Native Browser Alert)
// Sesuai Desain SILAP: Orange Accent, Clean Card, Smooth Pop
// ==========================================================
function showAppAlert({ title = "Informasi", message = "", type = "success", btnText = "Mengerti", onConfirm = null }) {
  if (typeof document === "undefined") return;

  let modal = document.getElementById("app-custom-alert-modal");
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "app-custom-alert-modal";
    modal.style.cssText = "position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.65);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);z-index:99999;display:none;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;";
    modal.innerHTML = `
      <div class="app-alert-card" style="background:#FFFFFF;border-radius:24px;width:100%;max-width:390px;box-shadow:0 25px 50px -12px rgba(15,23,42,0.25),0 0 0 1px rgba(226,232,240,0.8);padding:28px 24px 22px;text-align:center;box-sizing:border-box;transform:scale(0.92);opacity:0;transition:transform 0.22s cubic-bezier(0.16,1,0.3,1),opacity 0.22s ease;">
        <div id="app-alert-icon-wrap" style="width:64px;height:64px;border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;background:#ECFDF5;border:2px solid #A7F3D0;color:#059669;box-shadow:0 10px 20px -5px rgba(16,185,129,0.2);">
          <svg id="app-alert-icon-svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <h3 id="app-alert-title" style="margin:0 0 8px 0;font-size:1.18rem;font-weight:800;color:#0F172A;line-height:1.3;letter-spacing:-0.02em;">Pemberitahuan</h3>
        <div id="app-alert-message" style="font-size:0.88rem;color:#475569;line-height:1.55;margin-bottom:20px;"></div>
        <button type="button" id="app-alert-btn" style="width:100%;background:linear-gradient(135deg, #EA580C 0%, #C2410C 100%);color:#FFFFFF;border:none;border-radius:14px;padding:12px 18px;font-size:0.92rem;font-weight:800;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;box-shadow:0 6px 18px rgba(234,88,12,0.32);font-family:inherit;transition:transform 0.15s ease,box-shadow 0.15s ease;">
          <span id="app-alert-btn-text">Mengerti</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
      </div>
    `;
    document.body.appendChild(modal);
  }

  const card = modal.querySelector(".app-alert-card");
  const iconWrap = document.getElementById("app-alert-icon-wrap");
  const iconSvg = document.getElementById("app-alert-icon-svg");
  const titleEl = document.getElementById("app-alert-title");
  const msgEl = document.getElementById("app-alert-message");
  const btnTextEl = document.getElementById("app-alert-btn-text");
  const btn = document.getElementById("app-alert-btn");

  if (titleEl) titleEl.textContent = title;
  if (msgEl) msgEl.innerHTML = message;
  if (btnTextEl) btnTextEl.textContent = btnText;

  if (type === "success") {
    iconWrap.style.background = "#ECFDF5";
    iconWrap.style.borderColor = "#A7F3D0";
    iconWrap.style.color = "#059669";
    iconWrap.style.boxShadow = "0 10px 20px -5px rgba(16,185,129,0.2)";
    iconSvg.innerHTML = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>';
  } else if (type === "error") {
    iconWrap.style.background = "#FEF2F2";
    iconWrap.style.borderColor = "#FECACA";
    iconWrap.style.color = "#DC2626";
    iconWrap.style.boxShadow = "0 10px 20px -5px rgba(220,38,38,0.2)";
    iconSvg.innerHTML = '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>';
  } else {
    iconWrap.style.background = "#FFF7ED";
    iconWrap.style.borderColor = "#FED7AA";
    iconWrap.style.color = "#EA580C";
    iconWrap.style.boxShadow = "0 10px 20px -5px rgba(234,88,12,0.2)";
    iconSvg.innerHTML = '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>';
  }

  modal.style.display = "flex";
  requestAnimationFrame(() => {
    card.style.transform = "scale(1)";
    card.style.opacity = "1";
  });

  const closeModal = () => {
    card.style.transform = "scale(0.92)";
    card.style.opacity = "0";
    setTimeout(() => {
      modal.style.display = "none";
      if (typeof onConfirm === "function") onConfirm();
    }, 180);
  };

  btn.onclick = (e) => {
    e.preventDefault();
    closeModal();
  };

  modal.onclick = (e) => {
    if (e.target === modal) closeModal();
  };
}

if (typeof window !== "undefined") {
  window.showAppAlert = showAppAlert;
  window.alert = function(msg) {
    showAppAlert({
      title: "Pemberitahuan",
      message: String(msg),
      type: "info",
      btnText: "Mengerti"
    });
  };
}

