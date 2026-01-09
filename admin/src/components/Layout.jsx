import React, { useState } from 'react';
import { 
  Archive, 
  FileImage, 
  Settings, 
  Database, 
  Download,
  LayoutDashboard,
  Code
} from 'lucide-react';

export default function Layout({ children, activeTab, onTabChange }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);

  const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { id: 'collections', label: 'Coleções', icon: Archive },
    { id: 'items', label: 'Itens', icon: FileImage },
    { id: 'metadata', label: 'Metadados', icon: Database },
    { id: 'shortcodes', label: 'Shortcodes', icon: Code },
    { id: 'import', label: 'Importar', icon: Download },
    { id: 'settings', label: 'Configurações', icon: Settings },
  ];

  return (
    <div className="acervox-layout">
      {/* Sidebar */}
      <aside className={`acervox-sidebar ${sidebarOpen ? 'open' : ''}`}>
        <div className="acervox-sidebar-header">
          <div className="acervox-sidebar-logo">
            <Archive size={24} />
            <span>AcervoX</span>
          </div>
        </div>

        <nav className="acervox-sidebar-nav">
          {menuItems.map((item) => {
            const Icon = item.icon;
            return (
              <a
                key={item.id}
                href="#"
                className={`acervox-nav-item ${activeTab === item.id ? 'active' : ''}`}
                onClick={(e) => {
                  e.preventDefault();
                  onTabChange(item.id);
                }}
              >
                <Icon size={18} />
                <span>{item.label}</span>
              </a>
            );
          })}
        </nav>
      </aside>

      {/* Main Content */}
      <main className="acervox-main">
        <div className="acervox-content-wrapper">
          {children}
        </div>
      </main>
    </div>
  );
}
