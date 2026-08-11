import { ItemRequest, RequestStatus } from "../types";

export const normalizeRequestStatus = (status: string): RequestStatus => {
  const statusMap: Record<string, RequestStatus> = {
    DIAJUKAN: RequestStatus.DIAJUKAN,
    DICEK: RequestStatus.DICEK,
    TERPENUHI: RequestStatus.TERPENUHI,

    TERPENUHI_SEBAGIAN: RequestStatus.TERPENUHI_SEBAGIAN,
    "TERPENUHI SEBAGIAN": RequestStatus.TERPENUHI_SEBAGIAN,

    PERLU_PENGADAAN: RequestStatus.PERLU_PENGADAAN,
    "PERLU PENGADAAN": RequestStatus.PERLU_PENGADAAN,

    DALAM_PENGADAAN: RequestStatus.DALAM_PENGADAAN,
    "DALAM PENGADAAN": RequestStatus.DALAM_PENGADAAN,

    DITOLAK: RequestStatus.DITOLAK,
    SELESAI: RequestStatus.SELESAI,
  };

  return statusMap[status.toUpperCase()] ?? (status as RequestStatus);
};

export const toDateOnly = (
  value: string | null | undefined
): string => {
  return value ? value.substring(0, 10) : "";
};

export const normalizeRequest = (r: any): ItemRequest => ({
  id:               String(r.id),
  bonNo:            r.bon_no,
  section:          r.section,
  itemName:         r.item_name,
  qtyRequested:     Number(r.qty_requested  ?? 0),
  qtyAvailable:     Number(r.qty_available  ?? 0),
  qtyFulfilled:     Number(r.qty_fulfilled  ?? 0),
  qtyToProcure:     Number(r.qty_to_procure ?? 0),
  stockAllocated:   Boolean(r.stock_allocated),
  unit:             r.unit,
  status:           normalizeRequestStatus(r.status ?? ""),
  notes:            r.notes ?? "",
  date:             toDateOnly(r.date),
  requester:        r.requester,
  lastUpdated:      toDateOnly(r.last_updated),
  stockItemId:      r.stock_item_id ? String(r.stock_item_id) : undefined,
  procurementMethod: r.procurement_method ?? undefined,
  vendorName:       r.vendor_name ?? undefined,
  distribution:     r.distribution   ?? undefined,
  procurements:     r.procurements   ?? [],
  verifierNotes:    r.verifier_notes ?? undefined,
  userId:           r.user_id ? String(r.user_id) : undefined,
});
