@extends('layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Riwayat Upload Stok</h2>
        <a href="{{ route('stok-upload.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md shadow-sm">
            Upload Baru
        </a>
    </div>

    <div class="p-6">
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Statistik</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($uploads as $upload)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $upload->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium text-gray-900">{{ $upload->original_filename }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $upload->total_sheets }} sheet, {{ $upload->total_rows }} baris
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            User ID: {{ $upload->uploaded_by }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($upload->status === 'Selesai') bg-green-100 text-green-800
                                @elseif($upload->status === 'Menunggu Verifikasi') bg-yellow-100 text-yellow-800
                                @elseif($upload->status === 'Perlu Perbaikan') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif
                            ">
                                {{ $upload->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                            <div class="text-green-600">Valid: {{ $upload->valid_rows }}</div>
                            <div class="text-red-600">Perbaikan: {{ $upload->invalid_rows }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($upload->status === 'Menunggu Verifikasi' || $upload->status === 'Perlu Perbaikan')
                                <a href="{{ route('stok-upload.preview', $upload->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Preview</a>
                                <a href="{{ route('stok-upload.verifikasi.index', $upload->id) }}" class="text-blue-600 hover:text-blue-900">Verifikasi</a>
                            @else
                                <a href="{{ route('stok-upload.preview', $upload->id) }}" class="text-gray-500 hover:text-gray-900">Lihat Detail</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                            Belum ada riwayat upload.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $uploads->links() ?? '' }}
        </div>
    </div>
</div>
@endsection
