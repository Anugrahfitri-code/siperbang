import React, { useEffect } from 'react';
import { Navbar } from './LandingPageSections/Navbar';
import { HeroSection } from './LandingPageSections/HeroSection';
import { FeaturesSection } from './LandingPageSections/FeaturesSection';
import { HowItWorksSection } from './LandingPageSections/HowItWorksSection';
import { DashboardPreviewSection } from './LandingPageSections/DashboardPreviewSection';
import { TeamSection } from './LandingPageSections/TeamSection';
import { Footer } from './LandingPageSections/Footer';

interface LandingPageProps {
  onNavigateToLogin: () => void;
}

export const LandingPage: React.FC<LandingPageProps> = ({ onNavigateToLogin }) => {
  useEffect(() => {
    document.documentElement.style.scrollBehavior = 'smooth';
    return () => {
      document.documentElement.style.scrollBehavior = 'auto';
    };
  }, []);

  return (
    <div className="min-h-screen font-sans overflow-x-hidden" style={{ fontFamily: "'Inter', 'Instrument Sans', sans-serif" }}>
      <Navbar onNavigateToLogin={onNavigateToLogin} />
      <HeroSection onNavigateToLogin={onNavigateToLogin} />
      <FeaturesSection />
      <HowItWorksSection />
      <DashboardPreviewSection />
      <TeamSection />
      <Footer />
    </div>
  );
};
