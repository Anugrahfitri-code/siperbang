import React, { useState } from "react";
import { UserRole, UserAccount } from "../types";
import { Users, ShieldCheck, KeyRound, Plus, MoreVertical, Search, Edit2, Trash2 } from "lucide-react";
import { ConfirmDialog } from "./ConfirmDialog";

interface UserManagementProps {
  users: UserAccount[];
  onAddUser: (user: Omit<UserAccount, "id">) => void;
  onUpdateUser: (id: string, updates: Partial<UserAccount>) => void;
  onDeleteUser: (id: string) => void;
}

export function UserManagement({ users, onAddUser, onUpdateUser, onDeleteUser }: UserManagementProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<UserAccount | null>(null);

  // Form state
  const [formData, setFormData] = useState<Omit<UserAccount, "id">>({
    name: "",
    username: "",
    role: UserRole.PETUGAS_PERSERDIAN,
    status: "Aktif",
    section: ""
  });

  const filteredUsers = users.filter(u => 
    u.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
    u.username.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingId) {
      onUpdateUser(editingId, formData);
      setEditingId(null);
    } else {
      onAddUser(formData);
    }
    setShowAddForm(false);
    setFormData({
      name: "",
      username: "",
      role: UserRole.PETUGAS_PERSERDIAN,
      status: "Aktif",
      section: ""
    });
  };

  const startEdit = (user: UserAccount) => {
    setFormData({
      name: user.name,
      username: user.username,
      role: user.role,
      status: user.status,
      section: user.section || ""
    });
    setEditingId(user.id);
    setShowAddForm(true);
  };

  const handleDeleteConfirm = () => {
    if (confirmDelete) {
      onDeleteUser(confirmDelete.id);
      setConfirmDelete(null);
    }
  };

  return (
    <>
      {confirmDelete && (
        <ConfirmDialog
          open
          title="Hapus Akun"
          message={`Yakin ingin menghapus akun ${confirmDelete.name}? Akun ini akan dihapus permanen dan tidak dapat dikembalikan.`}
          variant="danger"
          confirmText="Hapus Akun"
          onConfirm={handleDeleteConfirm}
          onClose={() => setConfirmDelete(null)}
        />
      )}
    <div className="space-y-6">
      <div className="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
        <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
          <div className="flex items-center gap-3">
            <div className="bg-emerald-50 text-emerald-600 p-2.5 rounded border border-emerald-100">
              <Users size={18} />
            </div>
            <div>
              <h2 className="text-lg font-semibold leading-7 text-slate-900 uppercase">Kelola Akun Pengguna</h2>
              <p className="text-sm font-normal leading-5 text-slate-500 mt-0.5">
                Atur akses untuk Petugas Persediaan dan Ketua Tim.
              </p>
            </div>
          </div>
          
          <button
            onClick={() => {
              setEditingId(null);
              setFormData({ name: "", username: "", role: UserRole.PETUGAS_PERSERDIAN, status: "Aktif", section: "" });
              setShowAddForm(true);
            }}
            className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-xs font-bold transition-colors flex items-center justify-center gap-2"
          >
            <Plus size={16} />
            <span>Tambah Akun</span>
          </button>
        </div>

        {showAddForm && (
          <div className="bg-slate-50 border border-slate-200 rounded-lg p-5 mb-6">
            <h3 className="text-sm font-extrabold text-slate-800 mb-4">{editingId ? "Edit Akun" : "Tambah Akun Baru"}</h3>
            <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                  placeholder="Contoh: Budi Santoso"
                />
              </div>
              
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Username</label>
                <input
                  type="text"
                  required
                  value={formData.username}
                  onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                  placeholder="Contoh: budi.tu"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Peran Akses</label>
                <select
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value as UserRole })}
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                >
                  <option value={UserRole.PETUGAS_PERSERDIAN}>Petugas Persediaan</option>
                  <option value={UserRole.KETUA_TIM}>Ketua Tim Kerja</option>
                  <option value={UserRole.SUPERADMIN}>Superadmin</option>
                </select>
              </div>
              
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Unit Kerja / Seksi (Opsional)</label>
                <input
                  type="text"
                  value={formData.section}
                  onChange={(e) => setFormData({ ...formData, section: e.target.value })}
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                  placeholder="Contoh: Tata Usaha"
                  disabled={formData.role === UserRole.PETUGAS_PERSERDIAN || formData.role === UserRole.SUPERADMIN}
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">Status</label>
                <select
                  value={formData.status}
                  onChange={(e) => setFormData({ ...formData, status: e.target.value as "Aktif" | "Nonaktif" })}
                  className="w-full border border-slate-200 rounded-md px-3 py-2 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                >
                  <option value="Aktif">Aktif</option>
                  <option value="Nonaktif">Nonaktif</option>
                </select>
              </div>

              <div className="sm:col-span-2 flex justify-end gap-2 mt-2">
                <button
                  type="button"
                  onClick={() => setShowAddForm(false)}
                  className="px-4 py-2 border border-slate-300 rounded-md text-xs font-bold text-slate-600 hover:bg-slate-100"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-bold hover:bg-indigo-700"
                >
                  Simpan Akun
                </button>
              </div>
            </form>
          </div>
        )}

        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
          <input
            type="text"
            placeholder="Cari berdasarkan nama atau username..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-slate-50"
          />
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-slate-50 border-y border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                <th className="py-3 px-4">Nama & Username</th>
                <th className="py-3 px-4">Peran</th>
                <th className="py-3 px-4">Unit Kerja</th>
                <th className="py-3 px-4 text-center">Status</th>
                <th className="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {filteredUsers.length > 0 ? (
                filteredUsers.map((user) => (
                  <tr key={user.id} className="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                    <td className="py-3 px-4">
                      <div className="font-bold text-slate-800 text-xs">{user.name}</div>
                      <div className="text-xs text-slate-500 font-mono mt-0.5">@{user.username}</div>
                    </td>
                    <td className="py-3 px-4">
                      <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-extrabold ${
                        user.role === UserRole.SUPERADMIN ? "bg-emerald-50 text-emerald-700 border border-emerald-200" :
                        user.role === UserRole.PETUGAS_PERSERDIAN ? "bg-indigo-50 text-indigo-700 border border-indigo-200" :
                        "bg-amber-50 text-amber-700 border border-amber-200"
                      }`}>
                        {user.role === UserRole.SUPERADMIN && <ShieldCheck size={10} />}
                        {user.role === UserRole.PETUGAS_PERSERDIAN && <KeyRound size={10} />}
                        {user.role === UserRole.KETUA_TIM && <Users size={10} />}
                        {user.role === UserRole.SUPERADMIN ? "Superadmin" : user.role === UserRole.PETUGAS_PERSERDIAN ? "Petugas" : "Ketua Tim"}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-xs text-slate-600 font-medium">
                      {user.section || "-"}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <span className={`px-2 py-1 rounded text-xs font-bold ${
                        user.status === "Aktif" ? "bg-emerald-100 text-emerald-800" : "bg-rose-100 text-rose-800"
                      }`}>
                        {user.status}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-right">
                      <div className="flex justify-end gap-2">
                        <button
                          onClick={() => startEdit(user)}
                          className="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                          title="Edit"
                        >
                          <Edit2 size={14} />
                        </button>
                        <button
                          onClick={() => setConfirmDelete(user)}
                          className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors"
                          title="Hapus"
                          disabled={user.role === UserRole.SUPERADMIN}
                        >
                          <Trash2 size={14} className={user.role === UserRole.SUPERADMIN ? "opacity-30" : ""} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-slate-500 text-xs">
                    Tidak ada data pengguna yang sesuai.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    </>
  );
}
