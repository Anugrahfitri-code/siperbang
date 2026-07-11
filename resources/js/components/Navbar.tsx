import React, { useState, useRef, useEffect } from "react";
import { SiperbangLogo, KomdigiLogo } from "./Logos";
import { UserRole } from "../types";
import { Shield, User, RefreshCw, Menu, LogOut, ChevronDown } from "lucide-react";

interface NavbarProps {
  currentRole: UserRole;
  onChangeRole: (role: UserRole) => void;
  currentUser: string;
  onLogout: () => void;
  onToggleSidebar: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({
  currentRole,
  onChangeRole,
  currentUser,
  onLogout,
  onToggleSidebar,
}) => {
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const profileRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (profileRef.current && !profileRef.current.contains(event.target as Node)) {
        setIsProfileOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <header className="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-xs h-16 shrink-0 flex items-center">
      <div className="w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-full">
          {/* Brand Logos and Hamburger */}
          <div className="flex items-center gap-4 sm:gap-6">
            <button 
              onClick={onToggleSidebar}
              className="p-2 -ml-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors focus:outline-none"
              aria-label="Toggle Menu"
            >
              <Menu size={20} />
            </button>
            <SiperbangLogo />
            <div className="hidden md:block h-8 w-px bg-slate-200" />
            <KomdigiLogo className="hidden md:flex" />
          </div>

          {/* Quick Actions & Role Switcher */}
          <div className="flex items-center gap-4 relative" ref={profileRef}>
            {/* User Profile Button */}
            <button 
              onClick={() => setIsProfileOpen(!isProfileOpen)}
              className="flex items-center gap-2 hover:bg-slate-50 p-1.5 pr-2 rounded-lg transition-colors border border-transparent hover:border-slate-200"
            >
              <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm">
                <User size={16} />
              </div>
              <div className="flex flex-col text-left hidden sm:flex">
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-none mb-1">
                  {currentRole === UserRole.SUPERADMIN ? 'Superadmin' : currentRole === UserRole.PETUGAS_PERSERDIAN ? 'Petugas' : 'Ketua Tim'}
                </span>
                <span className="text-xs font-bold text-slate-800 leading-none flex items-center gap-1">
                  {currentUser.split(' ')[0]} <ChevronDown size={12} className="text-slate-400" />
                </span>
              </div>
            </button>

            {/* Profile Dropdown Menu */}
            {isProfileOpen && (
              <div className="absolute right-0 top-[110%] w-64 bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden z-50">
                <div className="p-4 border-b border-slate-100 bg-slate-50">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold">
                      {currentUser.charAt(0)}
                    </div>
                    <div>
                      <p className="text-sm font-extrabold text-slate-800 line-clamp-1" title={currentUser.split(' (')[0]}>{currentUser.split(' (')[0]}</p>
                      <p className="text-[11px] font-medium text-slate-500 mt-0.5">{currentRole === UserRole.SUPERADMIN ? 'Superadmin' : currentRole === UserRole.PETUGAS_PERSERDIAN ? 'Petugas Persediaan' : 'Ketua Tim Kerja'}</p>
                    </div>
                  </div>
                </div>
                <div className="p-2 bg-white">
                  <button
                    onClick={() => {
                      setIsProfileOpen(false);
                      onLogout();
                    }}
                    className="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors text-sm font-bold"
                  >
                    <LogOut size={16} />
                    <span>Keluar Akun</span>
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
};
