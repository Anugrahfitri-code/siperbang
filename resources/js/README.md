# Frontend React

- `features/`: komponen yang hanya dipakai oleh satu fitur.
- `shared/components/`: komponen lintas fitur.
- `shared/api.ts`: helper request HTTP.
- `shared/types.ts`: tipe domain lintas fitur.
- `App.tsx`: komposisi aplikasi dan state utama.
- `main.tsx`: entry point Vite.

Tambahkan komponen baru ke folder fitur yang sesuai. Gunakan `shared` hanya ketika komponen atau utilitas dipakai oleh lebih dari satu fitur.
