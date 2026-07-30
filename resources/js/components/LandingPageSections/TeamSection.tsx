import React from 'react';

export const TeamSection: React.FC = () => {
  const teamMembers = [
    {
      name: 'A. Izza Syathra',
      role: 'Project Manager',
      img: '/images/Tim-A.Izza Syathra.jpeg',
      github: '#',
      linkedin: '#',
    },
    {
      name: 'Anugrah Fitri Novanda',
      role: 'Backend Developer',
      img: '/images/Tim-Anugrah Fitri Novanda.jpeg',
      github: '#',
      linkedin: '#',
    },
    {
      name: 'Isnadia Nurfadilah',
      role: 'Frontend Developer',
      img: '/images/Tim-Isnadia Nurfadilah.jpeg',
      github: '#',
      linkedin: '#',
    },
    {
      name: 'Sita Rasmi Raihana',
      role: 'UI/UX Designer',
      img: '/images/Tim-Sita Rasmi Raihana.jpeg',
      github: '#',
      linkedin: '#',
    },
    {
      name: 'Siti Nur Fadhilah Az Zahra Syam',
      role: 'QA Engineer',
      img: '/images/Tim-Siti-Nur-Fadhilah-Az Zahra-Syam.jpeg',
      github: '#',
      linkedin: '#',
    },
    {
      name: 'Vina Sucitra',
      role: 'DevOps Engineer',
      img: '/images/Tim-Vina Sucitra.jpg',
      github: '#',
      linkedin: '#',
    },
  ];

  return (
    <section className="py-20 bg-white" id="tim">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="text-center mb-14">
          <h2 className="text-2xl font-extrabold text-[#0d1b2e] mb-3">Tim Pengembang</h2>
          <div className="w-14 h-1 bg-[#0055A5] rounded-full mx-auto"></div>
        </div>

        {/* Team Grid */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6">
          {teamMembers.map((member, i) => (
            <div key={i} className="flex flex-col items-center text-center group">
              {/* Circular Photo */}
              <div className="w-24 h-24 rounded-full overflow-hidden mb-4 border-4 border-white shadow-md group-hover:shadow-xl group-hover:-translate-y-1 group-hover:border-[#0055A5]/20 transition-all duration-300">
                <img
                  src={member.img}
                  alt={member.name}
                  className="w-full h-full object-cover object-top"
                />
              </div>
              <h4 className="text-sm font-bold text-[#0d1b2e] mb-0.5 leading-tight">{member.name}</h4>
              <p className="text-xs text-gray-400 mb-3">{member.role}</p>
              {/* Social Icons */}
              <div className="flex items-center gap-2">
                <a
                  href={member.linkedin}
                  className="w-7 h-7 rounded-md bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition-colors"
                  aria-label="LinkedIn"
                >
                  <svg viewBox="0 0 24 24" className="w-4 h-4" fill="currentColor">
                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                    <rect x="2" y="9" width="4" height="12"/>
                    <circle cx="4" cy="4" r="2"/>
                  </svg>
                </a>
                <a
                  href={member.github}
                  className="w-7 h-7 rounded-md bg-gray-800 text-white flex items-center justify-center hover:bg-gray-700 transition-colors"
                  aria-label="GitHub"
                >
                  <svg viewBox="0 0 24 24" className="w-4 h-4" fill="currentColor">
                    <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>
                  </svg>
                </a>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
