import React, { useEffect, useState } from 'react';
import { Archive, FileImage, Database, TrendingUp, Download, FileText, Code, Settings, Sparkles, ArrowRight, CheckCircle2, Clock, Users } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { useToast } from './ToastProvider';

export default function Dashboard() {
  const [stats, setStats] = useState({
    collections: 0,
    items: 0,
    metadata: 0,
    recent: [],
    recentImports: []
  });
  const [loading, setLoading] = useState(true);
  const { showToast } = useToast();

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      const [collectionsData, itemsData, historyData] = await Promise.all([
        fetch('/wp-json/acervox/v1/collections').then(r => r.json()),
        fetch('/wp-json/acervox/v1/items?per_page=5').then(r => r.json()),
        fetch('/wp-json/acervox/v1/import-history?per_page=3').then(r => r.json()).catch(() => ({ items: [] }))
      ]);

      // Calcular total de metadados
      let totalMetadata = 0;
      if (collectionsData.collections) {
        collectionsData.collections.forEach(col => {
          totalMetadata += col.fields_count || 0;
        });
      }

      setStats({
        collections: collectionsData.collections?.length || 0,
        items: itemsData.total || 0,
        metadata: totalMetadata,
        recent: itemsData.items?.slice(0, 5) || [],
        recentImports: historyData.items || []
      });
    } catch (error) {
      console.error('Erro ao carregar dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bom dia';
    if (hour < 18) return 'Boa tarde';
    return 'Boa noite';
  };

  const quickActions = [
    { id: 'collections', label: 'Gerenciar Coleções', icon: Archive, color: 'hsl(var(--primary))' },
    { id: 'items', label: 'Ver Itens', icon: FileImage, color: 'hsl(142, 76%, 36%)' },
    { id: 'metadata', label: 'Configurar Metadados', icon: Database, color: 'hsl(221, 83%, 53%)' },
    { id: 'import', label: 'Importar Dados', icon: Download, color: 'hsl(38, 92%, 50%)' }
  ];

  if (loading) {
    return (
      <div className="acervox-loading">
        <div className="acervox-spinner"></div>
      </div>
    );
  }

  return (
    <>
      <div className="acervox-header">
        <div>
          <h1 className="acervox-header-title" style={{ marginBottom: '8px' }}>
            {getGreeting()}!
          </h1>
          <p style={{ fontSize: '16px', color: 'hsl(var(--muted-foreground))', margin: 0 }}>
            Bem-vindo ao AcervoX - Seu sistema de gestão de acervos digitais
          </p>
        </div>
      </div>
      <div className="acervox-content">
        {/* Welcome Card */}
        <Card style={{ marginBottom: '32px', background: 'linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--primary)) 100%)', border: 'none' }}>
          <CardContent style={{ padding: '32px', color: 'white' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
              <div style={{ 
                width: '64px', 
                height: '64px', 
                borderRadius: '12px', 
                background: 'rgba(255, 255, 255, 0.2)', 
                display: 'flex', 
                alignItems: 'center', 
                justifyContent: 'center' 
              }}>
                <Sparkles size={32} style={{ color: 'white' }} />
              </div>
              <div style={{ flex: 1 }}>
                <h2 style={{ fontSize: '24px', fontWeight: 600, margin: '0 0 8px 0', color: 'white' }}>
                  Comece a organizar seu acervo
                </h2>
                <p style={{ fontSize: '15px', margin: 0, opacity: 0.9, color: 'white' }}>
                  Crie coleções, importe itens e configure metadados personalizados para gerenciar seu acervo digital de forma profissional.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Stats Grid */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '20px', marginBottom: '32px' }}>
          <Card style={{ transition: 'all 0.2s', cursor: 'pointer' }} 
                onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-2px)'}
                onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}>
            <CardHeader style={{ paddingBottom: '12px' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <CardTitle style={{ fontSize: '14px', fontWeight: 500, color: 'hsl(var(--muted-foreground))' }}>
                  Coleções
                </CardTitle>
                <Archive size={18} style={{ color: 'hsl(var(--muted-foreground))' }} />
              </div>
            </CardHeader>
            <CardContent>
              <div style={{ fontSize: '32px', fontWeight: 700, marginBottom: '4px', color: 'hsl(var(--foreground))' }}>
                {stats.collections}
              </div>
              <p style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))', margin: 0 }}>
                Total de coleções cadastradas
              </p>
            </CardContent>
          </Card>

          <Card style={{ transition: 'all 0.2s', cursor: 'pointer' }}
                onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-2px)'}
                onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}>
            <CardHeader style={{ paddingBottom: '12px' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <CardTitle style={{ fontSize: '14px', fontWeight: 500, color: 'hsl(var(--muted-foreground))' }}>
                  Itens
                </CardTitle>
                <FileImage size={18} style={{ color: 'hsl(var(--muted-foreground))' }} />
              </div>
            </CardHeader>
            <CardContent>
              <div style={{ fontSize: '32px', fontWeight: 700, marginBottom: '4px', color: 'hsl(var(--foreground))' }}>
                {stats.items}
              </div>
              <p style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))', margin: 0 }}>
                Itens no acervo
              </p>
            </CardContent>
          </Card>

          <Card style={{ transition: 'all 0.2s', cursor: 'pointer' }}
                onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-2px)'}
                onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}>
            <CardHeader style={{ paddingBottom: '12px' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <CardTitle style={{ fontSize: '14px', fontWeight: 500, color: 'hsl(var(--muted-foreground))' }}>
                  Metadados
                </CardTitle>
                <Database size={18} style={{ color: 'hsl(var(--muted-foreground))' }} />
              </div>
            </CardHeader>
            <CardContent>
              <div style={{ fontSize: '32px', fontWeight: 700, marginBottom: '4px', color: 'hsl(var(--foreground))' }}>
                {stats.metadata}
              </div>
              <p style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))', margin: 0 }}>
                Campos configurados
              </p>
            </CardContent>
          </Card>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '24px', marginBottom: '32px' }}>
          {/* Quick Actions */}
          <Card>
            <CardHeader>
              <CardTitle style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Sparkles size={18} />
                Ações Rápidas
              </CardTitle>
              <CardDescription>
                Acesso rápido às principais funcionalidades
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                {quickActions.map((action) => {
                  const Icon = action.icon;
                  return (
                    <Button
                      key={action.id}
                      variant="outline"
                      onClick={() => {
                        const event = new CustomEvent('acervox-navigate', { detail: { tab: action.id } });
                        window.dispatchEvent(event);
                      }}
                      style={{
                        justifyContent: 'flex-start',
                        padding: '16px',
                        height: 'auto',
                        border: '1px solid hsl(var(--border))',
                        transition: 'all 0.2s'
                      }}
                      onMouseEnter={(e) => {
                        e.currentTarget.style.borderColor = action.color;
                        e.currentTarget.style.background = action.color + '10';
                      }}
                      onMouseLeave={(e) => {
                        e.currentTarget.style.borderColor = 'hsl(var(--border))';
                        e.currentTarget.style.background = 'transparent';
                      }}
                    >
                      <Icon size={18} style={{ marginRight: '12px', color: action.color }} />
                      <span style={{ flex: 1, textAlign: 'left' }}>{action.label}</span>
                      <ArrowRight size={16} style={{ color: 'hsl(var(--muted-foreground))' }} />
                    </Button>
                  );
                })}
              </div>
            </CardContent>
          </Card>

          {/* Recent Imports */}
          <Card>
            <CardHeader>
              <CardTitle style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Clock size={18} />
                Importações Recentes
              </CardTitle>
              <CardDescription>
                Últimas importações realizadas
              </CardDescription>
            </CardHeader>
            <CardContent>
              {stats.recentImports.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '24px', color: 'hsl(var(--muted-foreground))' }}>
                  <Download size={32} style={{ margin: '0 auto 12px', opacity: 0.5 }} />
                  <p style={{ margin: 0, fontSize: '14px' }}>Nenhuma importação ainda</p>
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  {stats.recentImports.map((importItem) => (
                    <div
                      key={importItem.id}
                      style={{
                        padding: '12px',
                        background: 'hsl(var(--muted))',
                        borderRadius: '8px',
                        border: '1px solid hsl(var(--border))'
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <FileText size={14} />
                          <span style={{ fontSize: '13px', fontWeight: 600 }}>
                            {importItem.import_type === 'csv' ? 'CSV' : 'Sistema Externo'}
                          </span>
                        </div>
                        {importItem.status === 'completed' && (
                          <CheckCircle2 size={14} style={{ color: 'hsl(142, 76%, 36%)' }} />
                        )}
                      </div>
                      <div style={{ fontSize: '12px', color: 'hsl(var(--muted-foreground))' }}>
                        {importItem.imported_items} de {importItem.total_items} itens importados
                        {importItem.started_at_formatted && (
                          <span style={{ marginLeft: '8px' }}>• {importItem.started_at_formatted}</span>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Recent Items */}
        <Card>
          <CardHeader>
            <CardTitle>Itens Recentes</CardTitle>
            <CardDescription>Últimos itens adicionados ao acervo</CardDescription>
          </CardHeader>
          <CardContent>
            {stats.recent.length === 0 ? (
              <div className="acervox-empty">
                <div className="acervox-empty-icon">
                  <FileImage size={48} />
                </div>
                <div className="acervox-empty-title">Nenhum item encontrado</div>
                <div className="acervox-empty-description">
                  Comece adicionando itens ao seu acervo ou importe um arquivo CSV
                </div>
                <Button
                  variant="outline"
                  onClick={() => {
                    const event = new CustomEvent('acervox-navigate', { detail: { tab: 'import-csv' } });
                    window.dispatchEvent(event);
                  }}
                  style={{ marginTop: '16px' }}
                >
                  <Download size={16} style={{ marginRight: '8px' }} />
                  Importar CSV
                </Button>
              </div>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '20px' }}>
                {stats.recent.map(item => (
                  <div
                    key={item.id}
                    style={{
                      padding: '16px',
                      background: 'hsl(var(--muted))',
                      borderRadius: '12px',
                      border: '1px solid hsl(var(--border))',
                      transition: 'all 0.2s',
                      cursor: 'pointer'
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.transform = 'translateY(-4px)';
                      e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.transform = 'translateY(0)';
                      e.currentTarget.style.boxShadow = 'none';
                    }}
                  >
                    {item.thumbnails?.medium && (
                      <img
                        src={item.thumbnails.medium}
                        alt={item.title}
                        style={{
                          width: '100%',
                          height: '140px',
                          objectFit: 'cover',
                          borderRadius: '8px',
                          marginBottom: '12px',
                          background: 'hsl(var(--muted))'
                        }}
                      />
                    )}
                    <h4 style={{ fontSize: '14px', fontWeight: 600, marginBottom: '6px', color: 'hsl(var(--foreground))' }}>
                      {item.title}
                    </h4>
                    {item.excerpt && (
                      <p style={{ fontSize: '12px', color: 'hsl(var(--muted-foreground))', margin: 0, lineHeight: '1.5' }}>
                        {item.excerpt.substring(0, 80)}{item.excerpt.length > 80 ? '...' : ''}
                      </p>
                    )}
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}
