<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>Rekap BON Pengajuan</title>

    <style>
        @page {
            margin: 18mm 12mm 16mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #111827;
            font-size: 10px;
        }

        h1 {
            margin: 0 0 6px;
            text-align: center;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .meta {
            margin: 0 0 12px;
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta .label {
            width: 78px;
            font-weight: bold;
        }

        .recap {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        /*
         * Header tabel akan diulang otomatis
         * ketika PDF menjadi lebih dari satu halaman.
         */
        .recap thead {
            display: table-header-group;
        }

        .recap tr {
            page-break-inside: avoid;
        }

        .recap th,
        .recap td {
            border: 1px solid #374151;
            padding: 6px 5px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .recap th {
            background: #f3f4f6;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        /*
         * Dibuat sedikit tinggi supaya tersedia ruang paraf manual.
         */
        .signature {
            height: 32px;
        }

        .empty {
            padding: 18px 8px !important;
            color: #6b7280;
            text-align: center;
        }

        .footer-note {
            margin-top: 8px;
            color: #6b7280;
            font-size: 8px;
        }
    </style>
</head>

<body>

    <h1>
        Rekap BON Pengajuan
    </h1>

    <table class="meta">

        <tr>
            <td class="label">
                Ketua Tim
            </td>

            <td>
                : {{ $leader->name }}
            </td>
        </tr>

        <tr>
            <td class="label">
                Seksi
            </td>

            <td>
                : {{ $leader->section ?: '-' }}
            </td>
        </tr>

    </table>


    <table class="recap">

        <thead>
            <tr>

                <th style="width: 10%;">
                    Tanggal
                </th>

                <th style="width: 16%;">
                    Nomor BON
                </th>

                <th style="width: 24%;">
                    Nama Barang
                </th>

                <th style="width: 8%;">
                    Jumlah
                </th>

                <th style="width: 9%;">
                    Satuan
                </th>

                <th style="width: 20%;">
                    Yang Bermohon
                </th>

                <th style="width: 13%;">
                    Paraf
                </th>

            </tr>
        </thead>


        <tbody>

            @forelse ($items as $item)

                <tr>

                    <td class="center">
                        {{ optional($item->date)->format('d/m/Y') }}
                    </td>

                    <td>
                        {{ $item->bon_no }}
                    </td>

                    <td>
                        {{ $item->item_name }}
                    </td>

                    <td class="center">
                        {{ $item->approved_recap_qty }}
                    </td>

                    <td class="center">
                        {{ $item->unit }}
                    </td>

                    <td>
                        {{ $item->user?->name ?: $item->requester }}
                    </td>

                    <td class="signature">
                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="7"
                        class="empty"
                    >
                        Belum ada barang yang disetujui
                        untuk Ketua Tim ini.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>


    <div class="footer-note">
        Rekap hanya memuat barang yang sudah
        disetujui atau diproses oleh
        Petugas Persediaan.
    </div>

</body>
</html>
