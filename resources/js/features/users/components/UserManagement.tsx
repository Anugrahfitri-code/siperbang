import React, { useState } from "react";
import { UserRole, UserAccount } from "../../../shared/types";
import { Users, ShieldCheck, KeyRound, Plus, MoreVertical, Search, Edit2, Trash2, Eye, EyeOff } from "lucide-react";
import { ConfirmDialog } from "../../../shared/components/feedback/ConfirmDialog";

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
  const [showPassword, setShowPassword] = useState(false);

  // Form state
  const [formData, setFormData] = useState<Omit<UserAccount, "id">>({
    name: "",
    username: "",
    role: UserRole.PETUGAS_PERSERDIAN,
    status: "Aktif",
    section: "",
    password: ""
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
    setShowPassword(false);
    setFormData({
      name: "",
      username: "",
      role: UserRole.PETUGAS_PERSERDIAN,
      status: "Aktif",
      section: "",
      password: ""
    });
  };

  const startEdit = (user: UserAccount) => {
    setFormData({
      name: user.name,
      username: user.username,
      role: user.role,
      status: user.status,
      section: user.section || "",
      password: ""
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
    <div className="space-y-6 animate-fade-in">
      <div className="relative bg-gradient-to-r from-[#f8faff] to-[#f0f4ff] rounded-2xl border border-indigo-50/50 p-6 shadow-sm flex flex-col sm:flex-row justify-between sm:items-center gap-5 overflow-hidden">
        {/* Glow effects */}
        <div className="absolute right-0 top-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
        <div className="absolute left-0 bottom-0 w-48 h-48 bg-blue-500/5 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>

        <div className="flex items-center gap-4 relative z-10">
          <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm border border-slate-100 text-emerald-500">
            <Users size={26} strokeWidth={2.5} />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-slate-800 uppercase tracking-wide">Kelola Akun Pengguna</h2>
            <p className="text-xs font-medium text-slate-500 mt-1">
              Atur akses untuk Petugas Persediaan dan Ketua Tim.
            </p>
          </div>
        </div>
        
        <button
          onClick={() => {
            setEditingId(null);
            setFormData({ name: "", username: "", password: "", role: UserRole.PETUGAS_PERSERDIAN, status: "Aktif", section: "" });
            setShowPassword(false);
            setShowAddForm(true);
          }}
          className="relative z-10 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2"
        >
          <Plus size={16} strokeWidth={2.5} />
          <span>Tambah Akun</span>
        </button>
      </div>

      {showAddForm ? (
        <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
          <h3 className="text-sm font-extrabold text-slate-900 mb-6">{editingId ? "Edit Akun" : "Tambah Akun Baru"}</h3>
          <form onSubmit={handleSubmit} autoComplete="off" className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap</label>
              <input
                type="text"
                required
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition-all shadow-sm"
                placeholder="Contoh: Budi Santoso"
              />
            </div>
            
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">Username</label>
              <input
                type="text"
                required
                autoComplete="off"
                value={formData.username}
                onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition-all shadow-sm"
                placeholder="Contoh: budi.tu"
              />
            </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                  Password {editingId && <span className="text-slate-400 font-medium font-normal ml-1">(Kosongkan jika tidak diubah)</span>}
                </label>
                <div className="relative">
                  <input
                    type={showPassword ? "text" : "password"}
                    required={!editingId}
                    minLength={formData.password ? 8 : undefined}
                    pattern={formData.password ? "^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$" : undefined}
                    title="Password minimal 8 karakter, harus mengandung huruf besar, huruf kecil, dan simbol khusus."
                    autoComplete="new-password"
                    value={formData.password || ""}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    className="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-10 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition-all shadow-sm"
                    placeholder="••••••••"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none"
                  >
                    {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                  </button>
                </div>
              </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">Peran Akses</label>
              <select
                value={formData.role}
                onChange={(e) => setFormData({ ...formData, role: e.target.value as UserRole })}
                className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition-all shadow-sm appearance-none bg-white"
              >
                <option value={UserRole.PETUGAS_PERSERDIAN}>Petugas Persediaan</option>
                <option value={UserRole.KETUA_TIM}>Ketua Tim Kerja</option>
              </select>
            </div>
            
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">Unit Kerja / Seksi (Opsional)</label>
              <input
                type="text"
                value={formData.section}
                onChange={(e) => setFormData({ ...formData, section: e.target.value })}
                className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition-all shadow-sm"
                placeholder="Contoh: Tata Usaha"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">Status</label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as "Aktif" | "Nonaktif" })}
                className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:outline-none transition-all shadow-sm appearance-none bg-white"
              >
                <option value="Aktif">Aktif</option>
                <option value="Nonaktif">Nonaktif</option>
              </select>
            </div>

            <div className="sm:col-span-2 flex justify-end gap-3 mt-4 pt-4 border-t border-slate-100">
              <button
                type="button"
                onClick={() => setShowAddForm(false)}
                className="px-6 py-2.5 bg-white border border-rose-200 text-rose-600 rounded-xl text-xs font-bold hover:bg-rose-50 transition-colors shadow-sm"
              >
                Batal
              </button>
              <button
                type="submit"
                className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors shadow-sm"
              >
                Simpan Akun
              </button>
            </div>
          </form>
        </div>
      ) : (
        <div className="space-y-4">

          <div className="relative">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
            <input
              type="text"
              placeholder="Cari berdasarkan nama atau username..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-11 pr-4 py-3.5 text-xs font-semibold border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white shadow-sm transition-all"
            />
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
            <table className="w-full text-left border-collapse min-w-[700px]">
              <thead>
                <tr className="bg-slate-50/50 border-b border-slate-200 text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                  <th className="py-4 px-6">Nama & Username</th>
                  <th className="py-4 px-6">Peran</th>
                  <th className="py-4 px-6">Unit Kerja</th>
                  <th className="py-4 px-6">Status</th>
                  <th className="py-4 px-6 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredUsers.length > 0 ? (
                  filteredUsers.map((user) => (
                    <tr key={user.id} className="hover:bg-blue-50/30 transition-colors">
                      <td className="py-4 px-6">
                        <div className="font-extrabold text-slate-900 text-xs">{user.name}</div>
                        <div className="text-xs text-slate-400 font-medium mt-0.5">@{user.username}</div>
                      </td>
                      <td className="py-4 px-6">
                        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-extrabold tracking-wide uppercase ${
                          user.role === UserRole.SUPERADMIN ? "bg-emerald-50 text-emerald-700" :
                          user.role === UserRole.PETUGAS_PERSERDIAN ? "bg-indigo-50 text-indigo-700" :
                          "bg-amber-50 text-amber-700"
                        }`}>
                          {user.role === UserRole.SUPERADMIN && <ShieldCheck size={12} strokeWidth={2.5} />}
                          {user.role === UserRole.PETUGAS_PERSERDIAN && <KeyRound size={12} strokeWidth={2.5} />}
                          {user.role === UserRole.KETUA_TIM && <Users size={12} strokeWidth={2.5} />}
                          {user.role === UserRole.SUPERADMIN ? "Superadmin" : user.role === UserRole.PETUGAS_PERSERDIAN ? "Petugas" : "Ketua Tim"}
                        </span>
                      </td>
                      <td className="py-4 px-6 text-xs text-slate-600 font-semibold">
                        {user.section || "-"}
                      </td>
                      <td className="py-4 px-6">
                        <span className={`text-xs font-extrabold ${
                          user.status?.toLowerCase() === "aktif" ? "text-emerald-500" : "text-rose-500"
                        }`}>
                          {user.status?.toLowerCase() === 'aktif' ? 'Aktif' : 'Nonaktif'}
                        </span>
                      </td>
                      <td className="py-4 px-6 text-right">
                        <div className="flex justify-end gap-2">
                          <button
                            onClick={() => startEdit(user)}
                            className="p-1.5 text-slate-400 hover:text-indigo-600 transition-colors"
                            title="Edit"
                          >
                            <Edit2 size={16} />
                          </button>
                          <button
                            onClick={() => setConfirmDelete(user)}
                            className="p-1.5 text-slate-400 hover:text-rose-600 transition-colors"
                            title="Hapus"
                            disabled={user.role === UserRole.SUPERADMIN}
                          >
                            <Trash2 size={16} className={user.role === UserRole.SUPERADMIN ? "opacity-30" : ""} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={5} className="py-12 text-center text-slate-500 text-xs font-medium">
                      Tidak ada data pengguna yang sesuai.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
    </>
  );
}
