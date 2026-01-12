import React, { useState } from 'react';
import { 
  Archive, 
  FileImage, 
  Settings, 
  Database, 
  Download,
  LayoutDashboard,
  Code,
  ChevronDown,
  ChevronRight,
  FileText,
  History
} from 'lucide-react';

export default function Layout({ children, activeTab, onTabChange }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [importMenuOpen, setImportMenuOpen] = useState(false);

  // Listener para navegação via eventos (usado no Dashboard)
  React.useEffect(() => {
    const handleNavigate = (event) => {
      if (event.detail && event.detail.tab) {
        onTabChange(event.detail.tab);
        // Se for import, abrir o submenu
        if (event.detail.tab.startsWith('import')) {
          setImportMenuOpen(true);
        }
      }
    };

    window.addEventListener('acervox-navigate', handleNavigate);
    return () => window.removeEventListener('acervox-navigate', handleNavigate);
  }, [onTabChange]);

  const menuItems = [
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { id: 'collections', label: 'Coleções', icon: Archive },
    { id: 'items', label: 'Itens', icon: FileImage },
    { id: 'metadata', label: 'Metadados', icon: Database },
    { id: 'shortcodes', label: 'Shortcodes', icon: Code },
    { 
      id: 'import', 
      label: 'Importar', 
      icon: Download,
      submenu: [
        { id: 'import', label: 'Sistema Externo', icon: Download },
        { id: 'import-csv', label: 'CSV', icon: FileText },
        { id: 'import-history', label: 'Histórico', icon: History }
      ]
    },
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
            const hasSubmenu = item.submenu && item.submenu.length > 0;
            const isImportActive = item.id === 'import' && (activeTab === 'import' || activeTab === 'import-csv' || activeTab === 'import-history');
            
            if (hasSubmenu) {
              return (
                <div key={item.id}>
                  <a
                    href="#"
                    className={`acervox-nav-item ${isImportActive ? 'active' : ''}`}
                    onClick={(e) => {
                      e.preventDefault();
                      setImportMenuOpen(!importMenuOpen);
                      if (!importMenuOpen) {
                        onTabChange(item.submenu[0].id);
                      }
                    }}
                  >
                    <Icon size={18} />
                    <span>{item.label}</span>
                    <span style={{ marginLeft: 'auto' }}>
                      {importMenuOpen ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                    </span>
                  </a>
                  {importMenuOpen && (
                    <div className="acervox-nav-submenu">
                      {item.submenu.map((subItem) => {
                        const SubIcon = subItem.icon;
                        return (
                          <a
                            key={subItem.id}
                            href="#"
                            className={`acervox-nav-item acervox-nav-subitem ${activeTab === subItem.id ? 'active' : ''}`}
                            onClick={(e) => {
                              e.preventDefault();
                              onTabChange(subItem.id);
                            }}
                          >
                            <SubIcon size={16} />
                            <span>{subItem.label}</span>
                          </a>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            }
            
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
