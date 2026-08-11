import React, {
  useCallback,
  useEffect,
  useMemo,
  useState,
} from "react";

import {
  AlertCircle,
  Download,
  FileText,
  Loader2,
  PackageSearch,
  RefreshCw,
  ShoppingCart,
  Users,
} from "lucide-react";

import {
  apiFetch,
} from "../../../shared/api";

import {
  UserAccount,
  RequestStatus,
} from "../../../shared/types";


interface ProcurementRow {
  item_name: string;
  unit: string;
  qty_to_procure: number;
  bon_count: number;
  bon_numbers: string[];
  requesters: string[];
}


interface ProcurementResponse {
  data: ProcurementRow[];
  total_item_types: number;
  total_qty_to_procure: number;
}


interface BonRecapPanelProps {
  teamLeaders?: UserAccount[];
  requests?: any[];
  hidePreview?: boolean;
}


export const BonRecapPanel:
React.FC<BonRecapPanelProps> = ({
  teamLeaders = [],
  requests = [],
  hidePreview = false,
}) => {

  const [
    selectedLeaderId,
    setSelectedLeaderId,
  ] = useState("");

  const [
    procurement,
    setProcurement,
  ] = useState<ProcurementResponse>({
    data: [],
    total_item_types: 0,
    total_qty_to_procure: 0,
  });

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    error,
    setError,
  ] = useState<string | null>(null);


  /*
   * Tidak melakukan request users baru.
   * Data Ketua Tim menggunakan state users
   * yang sudah dimiliki App.tsx.
   */
  const sortedLeaders = useMemo(
    () =>
      [...teamLeaders].sort(
        (a, b) =>
          a.name.localeCompare(
            b.name,
            "id-ID"
          )
      ),
    [teamLeaders]
  );


  /*
   * Otomatis pilih Ketua Tim pertama.
   */
  useEffect(() => {

    if (
      !selectedLeaderId
      && sortedLeaders.length > 0
    ) {
      setSelectedLeaderId(
        sortedLeaders[0].id
      );
    }

  }, [
    selectedLeaderId,
    sortedLeaders,
  ]);


  /*
   * Ambil daftar barang yang masih perlu dibeli.
   */
  const loadProcurement =
    useCallback(async () => {

      setLoading(true);
      setError(null);

      try {

        const response =
          await apiFetch(
            "/api/requests/recap/procurement"
          );

        const payload =
          await response
            .json()
            .catch(() => ({}));

        if (!response.ok) {
          throw new Error(
            payload.message
            || "Gagal memuat rekap pengadaan."
          );
        }

        setProcurement({
          data:
            Array.isArray(payload.data)
              ? payload.data
              : [],

          total_item_types:
            Number(
              payload.total_item_types
              ?? 0
            ),

          total_qty_to_procure:
            Number(
              payload.total_qty_to_procure
              ?? 0
            ),
        });

      } catch (err) {

        setError(
          err instanceof Error
            ? err.message
            : "Gagal memuat rekap pengadaan."
        );

      } finally {

        setLoading(false);

      }

    }, []);


  useEffect(() => {
    loadProcurement();
  }, [loadProcurement, requests]);

  const pdfPreviewData = useMemo(() => {
    if (!selectedLeaderId) return [];

    return requests
      .filter((r) => {
        if (r.userId !== selectedLeaderId) return false;

        const validStatuses = [
          RequestStatus.TERPENUHI,
          RequestStatus.TERPENUHI_SEBAGIAN,
          RequestStatus.SIAP_DIDISTRIBUSIKAN,
          RequestStatus.PERLU_PENGADAAN,
          RequestStatus.DALAM_PENGADAAN,
          RequestStatus.SELESAI,
        ];
        
        const isApprovedStatus = validStatuses.includes(r.status);
        const isPartiallyApprovedReject = r.status === RequestStatus.DITOLAK && r.qtyFulfilled > 0;
        
        return isApprovedStatus || isPartiallyApprovedReject;
      })
      .map((r) => {
        const approvedQty = r.status === RequestStatus.PERLU_PENGADAAN ? r.qtyRequested : r.qtyFulfilled;
        return {
          ...r,
          approvedQty,
        };
      })
      .sort((a, b) => Number(b.id) - Number(a.id));
  }, [requests, selectedLeaderId]);


  /*
   * Route PDF menggunakan session login Laravel.
   * Karena response Content-Disposition = attachment,
   * browser akan langsung mengunduh PDF.
   */
  const pdfUrl =
    selectedLeaderId
      ? `/api/requests/recap/pdf?user_id=${
          encodeURIComponent(
            selectedLeaderId
          )
        }`
      : "#";


  return (

    <div className="space-y-6">


      {/* =====================================================
          EXPORT PDF PER KETUA TIM
          Hanya muncul jika teamLeaders diberikan oleh App.
          ===================================================== */}

      {sortedLeaders.length > 0 && (

        <section
          className="
            rounded-2xl
            border border-slate-200
            bg-white
            p-5 sm:p-6
            shadow-sm
          "
        >

          <div
            className="
              flex flex-col
              gap-5
              lg:flex-row
              lg:items-center
              lg:justify-between
            "
          >

            <div
              className="
                flex
                items-start
                gap-4
              "
            >

              <div
                className="
                  flex
                  size-12
                  shrink-0
                  items-center
                  justify-center
                  rounded-2xl
                  border
                  border-blue-100
                  bg-blue-50
                  text-blue-600
                "
              >
                <FileText size={23} />
              </div>


              <div>

                <h3
                  className="
                    text-sm
                    font-extrabold
                    uppercase
                    tracking-wide
                    text-slate-800
                  "
                >
                  Rekap BON Pengajuan
                  per Ketua Tim
                </h3>


                <p
                  className="
                    mt-1
                    max-w-2xl
                    text-xs
                    font-medium
                    leading-relaxed
                    text-slate-500
                  "
                >
                  Ekspor PDF hanya memuat
                  barang yang sudah disetujui
                  atau diproses oleh
                  Petugas Persediaan.
                </p>

              </div>

            </div>


            <div
              className="
                flex
                w-full
                flex-col
                gap-3
                sm:flex-row
                lg:w-auto
              "
            >

              <div
                className="
                  relative
                  min-w-0
                  sm:min-w-[260px]
                "
              >

                <Users
                  size={15}
                  className="
                    pointer-events-none
                    absolute
                    left-3
                    top-1/2
                    -translate-y-1/2
                    text-slate-400
                  "
                />


                <select
                  value={selectedLeaderId}

                  onChange={(event) =>
                    setSelectedLeaderId(
                      event.target.value
                    )
                  }

                  className="
                    w-full
                    appearance-none
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    py-2.5
                    pl-9
                    pr-3
                    text-xs
                    font-bold
                    text-slate-700
                    outline-none
                    transition
                    focus:border-blue-400
                    focus:ring-2
                    focus:ring-blue-100
                  "
                >

                  {sortedLeaders.map(
                    (leader) => (

                      <option
                        key={leader.id}
                        value={leader.id}
                      >
                        {leader.name}

                        {leader.section
                          ? ` — ${leader.section}`
                          : ""}
                      </option>

                    )
                  )}

                </select>

              </div>


              <a
                href={pdfUrl}

                aria-disabled={
                  !selectedLeaderId
                }

                onClick={(event) => {

                  if (!selectedLeaderId) {
                    event.preventDefault();
                  }

                }}

                className={`
                  inline-flex
                  items-center
                  justify-center
                  gap-2
                  rounded-xl
                  px-4
                  py-2.5
                  text-xs
                  font-extrabold
                  transition

                  ${
                    selectedLeaderId

                      ? `
                        bg-blue-600
                        text-white
                        shadow-sm
                        hover:bg-blue-700
                      `

                      : `
                        cursor-not-allowed
                        bg-slate-100
                        text-slate-400
                      `
                  }
                `}
              >

                <Download size={15} />

                Ekspor PDF

              </a>

            </div>

          </div>

          {/* PDF Preview Table */}
          {selectedLeaderId && pdfPreviewData.length > 0 && (
            <div className="mt-6 border-t border-slate-100 pt-6">
              <div className="mb-3 flex items-center justify-between">
                <h4 className="text-xs font-bold uppercase tracking-wide text-slate-700">
                  Pratinjau Isi PDF
                </h4>
                <span className="text-xs font-medium text-slate-500">
                  {pdfPreviewData.length} Barang
                </span>
              </div>
              
              <div className="overflow-x-auto rounded-xl border border-slate-200">
                <table className="w-full text-left text-sm text-slate-600">
                  <thead className="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                    <tr>
                      <th className="px-4 py-3">Tgl</th>
                      <th className="px-4 py-3">No. BON</th>
                      <th className="px-4 py-3">Nama Barang</th>
                      <th className="px-4 py-3 text-right">Disetujui</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 bg-white">
                    {pdfPreviewData.map((item: any) => (
                      <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                        <td className="whitespace-nowrap px-4 py-3 font-medium text-slate-700">
                          {item.date}
                        </td>
                        <td className="whitespace-nowrap px-4 py-3 text-xs">
                          {item.bonNo}
                        </td>
                        <td className="px-4 py-3 font-medium text-slate-800">
                          {item.itemName}
                        </td>
                        <td className="whitespace-nowrap px-4 py-3 text-right font-bold text-blue-600">
                          {item.approvedQty} {item.unit}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {selectedLeaderId && pdfPreviewData.length === 0 && (
            <div className="mt-6 border-t border-slate-100 pt-6 text-center text-sm font-medium text-slate-500">
              Tidak ada barang yang disetujui untuk Ketua Tim ini.
            </div>
          )}

        </section>

      )}


      {/* =====================================================
          PREVIEW REKAP PENGADAAN
          ===================================================== */}

      {!hidePreview && (
      <section
        className="
          overflow-hidden
          rounded-2xl
          border
          border-slate-200
          bg-white
          shadow-sm
        "
      >

        <div
          className="
            flex
            flex-col
            gap-4
            border-b
            border-slate-100
            p-5
            sm:flex-row
            sm:items-center
            sm:justify-between
            sm:p-6
          "
        >

          <div
            className="
              flex
              items-start
              gap-4
            "
          >

            <div
              className="
                flex
                size-12
                shrink-0
                items-center
                justify-center
                rounded-2xl
                border
                border-amber-100
                bg-amber-50
                text-amber-600
              "
            >
              <ShoppingCart size={23} />
            </div>


            <div>

              <h3
                className="
                  text-sm
                  font-extrabold
                  uppercase
                  tracking-wide
                  text-slate-800
                "
              >
                Preview Rekap Pengadaan
              </h3>


              <p
                className="
                  mt-1
                  text-xs
                  font-medium
                  text-slate-500
                "
              >
                Sisa barang dari pengajuan
                Ketua Tim yang masih harus
                dibeli Petugas Persediaan.
              </p>

            </div>

          </div>


          <button
            type="button"

            onClick={
              loadProcurement
            }

            disabled={
              loading
            }

            className="
              inline-flex
              items-center
              justify-center
              gap-2
              rounded-xl
              border
              border-slate-200
              bg-white
              px-3.5
              py-2.5
              text-xs
              font-bold
              text-slate-600
              transition
              hover:bg-slate-50
              disabled:cursor-not-allowed
              disabled:opacity-60
            "
          >

            {loading ? (

              <Loader2
                size={14}
                className="animate-spin"
              />

            ) : (

              <RefreshCw size={14} />

            )}

            Perbarui

          </button>

        </div>


        {/* Statistik */}

        {!loading && !error && (

          <div
            className="
              grid
              grid-cols-2
              gap-px
              border-b
              border-slate-100
              bg-slate-100
              sm:grid-cols-3
            "
          >

            <div
              className="
                bg-white
                px-5
                py-4
              "
            >

              <p
                className="
                  text-[10px]
                  font-extrabold
                  uppercase
                  tracking-wider
                  text-slate-400
                "
              >
                Jenis Barang
              </p>

              <p
                className="
                  mt-1
                  text-xl
                  font-extrabold
                  text-slate-800
                "
              >
                {
                  procurement
                    .total_item_types
                }
              </p>

            </div>


            <div
              className="
                bg-white
                px-5
                py-4
              "
            >

              <p
                className="
                  text-[10px]
                  font-extrabold
                  uppercase
                  tracking-wider
                  text-slate-400
                "
              >
                Total Kebutuhan
              </p>

              <p
                className="
                  mt-1
                  text-xl
                  font-extrabold
                  text-slate-800
                "
              >
                {
                  procurement
                    .total_qty_to_procure
                }
              </p>

            </div>


            <div
              className="
                col-span-2
                bg-white
                px-5
                py-4
                sm:col-span-1
              "
            >

              <p
                className="
                  text-[10px]
                  font-extrabold
                  uppercase
                  tracking-wider
                  text-slate-400
                "
              >
                Status
              </p>

              <p
                className="
                  mt-1
                  text-sm
                  font-extrabold
                  text-amber-700
                "
              >
                Masih perlu dibeli
              </p>

            </div>

          </div>

        )}


        {/* Loading */}

        {loading ? (

          <div
            className="
              flex
              min-h-44
              flex-col
              items-center
              justify-center
              gap-2
              p-8
              text-slate-400
            "
          >

            <Loader2
              size={24}
              className="
                animate-spin
                text-amber-500
              "
            />

            <p
              className="
                text-xs
                font-semibold
              "
            >
              Memuat rekap pengadaan...
            </p>

          </div>


        ) : error ? (

          <div
            className="
              flex
              min-h-44
              flex-col
              items-center
              justify-center
              gap-2
              p-8
              text-center
            "
          >

            <AlertCircle
              size={24}
              className="text-rose-500"
            />

            <p
              className="
                text-sm
                font-extrabold
                text-slate-700
              "
            >
              Rekap pengadaan
              gagal dimuat
            </p>

            <p
              className="
                text-xs
                text-slate-500
              "
            >
              {error}
            </p>

          </div>


        ) : procurement.data.length === 0 ? (

          <div
            className="
              flex
              min-h-44
              flex-col
              items-center
              justify-center
              gap-2
              p-8
              text-center
            "
          >

            <PackageSearch
              size={28}
              className="
                text-emerald-500
              "
            />

            <p
              className="
                text-sm
                font-extrabold
                text-slate-700
              "
            >
              Tidak ada kebutuhan
              pengadaan
            </p>

            <p
              className="
                text-xs
                text-slate-500
              "
            >
              Semua kebutuhan saat ini
              sudah terpenuhi atau
              tidak perlu dibeli.
            </p>

          </div>


        ) : (

          <div className="overflow-x-auto">

            <table
              className="
                min-w-full
                divide-y
                divide-slate-100
                text-left
              "
            >

              <thead className="bg-slate-50">

                <tr
                  className="
                    text-[10px]
                    font-extrabold
                    uppercase
                    tracking-wider
                    text-slate-500
                  "
                >

                  <th className="px-5 py-3">
                    Nama Barang
                  </th>

                  <th
                    className="
                      px-5
                      py-3
                      text-center
                    "
                  >
                    Perlu Dibeli
                  </th>

                  <th
                    className="
                      px-5
                      py-3
                      text-center
                    "
                  >
                    Satuan
                  </th>

                  <th className="px-5 py-3">
                    Ketua Tim
                  </th>

                  <th className="px-5 py-3">
                    Nomor BON
                  </th>

                </tr>

              </thead>


              <tbody
                className="
                  divide-y
                  divide-slate-100
                  bg-white
                "
              >

                {procurement.data.map(
                  (row) => (

                    <tr
                      key={
                        `${row.item_name}-${row.unit}`
                      }

                      className="
                        text-xs
                        text-slate-600
                      "
                    >

                      <td
                        className="
                          px-5
                          py-4
                          font-extrabold
                          text-slate-800
                        "
                      >
                        {row.item_name}
                      </td>


                      <td
                        className="
                          px-5
                          py-4
                          text-center
                          text-sm
                          font-extrabold
                          text-amber-700
                        "
                      >
                        {
                          row
                            .qty_to_procure
                        }
                      </td>


                      <td
                        className="
                          px-5
                          py-4
                          text-center
                          font-bold
                        "
                      >
                        {row.unit}
                      </td>


                      <td
                        className="
                          px-5
                          py-4
                          font-semibold
                        "
                      >
                        {
                          row.requesters
                            .join(", ")
                          || "—"
                        }
                      </td>


                      <td
                        className="
                          px-5
                          py-4
                          font-mono
                          text-[11px]
                          font-bold
                          text-slate-500
                        "
                      >
                        {
                          row.bon_numbers
                            .join(", ")
                          || "—"
                        }
                      </td>

                    </tr>

                  )
                )}

              </tbody>

            </table>

          </div>

        )}

      </section>
      )}

    </div>

  );
};
