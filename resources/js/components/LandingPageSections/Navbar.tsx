import React, { useState, useEffect } from 'react';
import { LogIn, Menu, X } from 'lucide-react';
import { SiperbangLogo, KomdigiLogo } from '../Logos';

interface NavbarProps {
  onNavigateToLogin: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({ onNavigateToLogin }) => {
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const [activeLink, setActiveLink] = useState('Beranda');

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const navLinks = ['Beranda', 'Tentang', 'Fitur', 'Panduan', 'FAQ', 'Kontak'];

  return (
    <nav className={`sticky top-0 z-50 bg-white transition-shadow duration-300 ${scrolled ? 'shadow-md' : 'shadow-sm border-b border-gray-100'}`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-[68px]">
          {/* Left: Logos */}
          <div className="flex items-center gap-5 cursor-pointer" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>
            <SiperbangLogo />
            <div className="w-px h-10 bg-gray-200 hidden sm:block"></div>
            <div className="hidden sm:block">
              <KomdigiLogo />
            </div>
          </div>

          {/* Center: Nav Links */}
          <div className="hidden lg:flex items-center gap-1">
            {navLinks.map((link) => (
              <a
                key={link}
                href={`#${link.toLowerCase()}`}
                onClick={() => setActiveLink(link)}
                className={`px-4 py-2 text-sm font-semibold transition-colors relative group ${
                  activeLink === link ? 'text-[#0055A5]' : 'text-gray-600 hover:text-[#0055A5]'
                }`}
              >
                {link}
                <span className={`absolute bottom-0 left-0 right-0 h-[2px] bg-[#0055A5] rounded-full transition-transform duration-200 ${
                  activeLink === link ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100'
                }`}></span>
              </a>
            ))}
          </div>

          {/* Right: Button */}
          <div className="flex items-center gap-3">
            <button
              onClick={onNavigateToLogin}
              className="hidden lg:flex items-center gap-2 bg-[#0055A5] text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-[#013A70] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            >
              <LogIn size={16} />
              Masuk
            </button>
            <button className="lg:hidden p-2 text-gray-600" onClick={() => setMenuOpen(!menuOpen)}>
              {menuOpen ? <X size={22} /> : <Menu size={22} />}
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {menuOpen && (
          <div className="lg:hidden border-t border-gray-100 py-4 space-y-1">
            {navLinks.map((link) => (
              <a
                key={link}
                href={`#${link.toLowerCase()}`}
                className="block px-4 py-2.5 text-sm font-semibold text-gray-600 hover:text-[#0055A5] hover:bg-blue-50 rounded-lg"
                onClick={() => setMenuOpen(false)}
              >
                {link}
              </a>
            ))}
            <div className="pt-2 px-4">
              <button
                onClick={onNavigateToLogin}
                className="w-full flex items-center justify-center gap-2 bg-[#0055A5] text-white px-5 py-2.5 rounded-lg text-sm font-bold"
              >
                <LogIn size={16} /> Masuk
              </button>
            </div>
          </div>
        )}
      </div>
    </nav>
  );
};
