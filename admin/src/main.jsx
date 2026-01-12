import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import './styles.css';

import Layout from './components/Layout';
import Dashboard from './components/Dashboard';
import Collections from './components/Collections';
import Items from './components/Items';
import MetadataBuilder from './components/MetadataBuilder';
import Shortcodes from './components/Shortcodes';
import ImportTainacan from './components/ImportTainacan';
import ImportCSV from './components/ImportCSV';
import ImportHistory from './components/ImportHistory';
import Settings from './components/Settings';

function App() {
  // Carregar estado salvo do localStorage
  const [activeTab, setActiveTab] = useState(() => {
    const saved = localStorage.getItem('acervox_active_tab');
    return saved || 'dashboard';
  });

  // Salvar estado no localStorage quando mudar
  useEffect(() => {
    localStorage.setItem('acervox_active_tab', activeTab);
  }, [activeTab]);

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <Dashboard />;
      case 'collections':
        return <Collections />;
      case 'items':
        return <Items />;
      case 'metadata':
        return <MetadataBuilder />;
      case 'shortcodes':
        return <Shortcodes />;
      case 'import':
        return <ImportTainacan />;
      case 'import-csv':
        return <ImportCSV />;
      case 'import-history':
        return <ImportHistory />;
      case 'settings':
        return <Settings />;
      default:
        return <Dashboard />;
    }
  };

  return (
    <div className="acervox-admin">
      <Layout activeTab={activeTab} onTabChange={setActiveTab}>
        {renderContent()}
      </Layout>
    </div>
  );
}

const container = document.getElementById('acervox-admin');

if (container) {
  const root = ReactDOM.createRoot(container);
  root.render(<App />);
}
