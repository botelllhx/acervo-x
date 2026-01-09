import React, { useEffect, useState } from 'react';
import { Archive, FileImage, Database, TrendingUp } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';

export default function Dashboard() {
  const [stats, setStats] = useState({
    collections: 0,
    items: 0,
    metadata: 0,
    recent: []
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      fetch('/wp-json/acervox/v1/collections').then(r => r.json()),
      fetch('/wp-json/acervox/v1/items?per_page=1').then(r => r.json()),
    ]).then(([collectionsData, itemsData]) => {
      setStats({
        collections: collectionsData.collections?.length || 0,
        items: itemsData.total || 0,
        metadata: 0,
        recent: itemsData.items?.slice(0, 5) || []
      });
      setLoading(false);
    }).catch(() => setLoading(false));
  }, []);

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
        <h1 className="acervox-header-title">Dashboard</h1>
      </div>
      <div className="acervox-content">
        {/* Stats Grid */}
        <div className="acervox-grid acervox-grid-cols-3" style={{ marginBottom: '32px' }}>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Coleções</CardTitle>
              <Archive className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.collections}</div>
              <p className="text-xs text-muted-foreground">Total de coleções</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Itens</CardTitle>
              <FileImage className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.items}</div>
              <p className="text-xs text-muted-foreground">Itens no acervo</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Metadados</CardTitle>
              <Database className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.metadata}</div>
              <p className="text-xs text-muted-foreground">Campos configurados</p>
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
                  Comece adicionando itens ao seu acervo
                </div>
              </div>
            ) : (
              <div className="acervox-grid acervox-grid-cols-4">
                {stats.recent.map(item => (
                  <div key={item.id} className="acervox-card" style={{ padding: '16px' }}>
                    {item.thumbnails?.medium && (
                      <img
                        src={item.thumbnails.medium}
                        alt={item.title}
                        style={{
                          width: '100%',
                          height: '120px',
                          objectFit: 'cover',
                          borderRadius: '6px',
                          marginBottom: '12px'
                        }}
                      />
                    )}
                    <h4 style={{ fontSize: '14px', fontWeight: 600, marginBottom: '4px' }}>
                      {item.title}
                    </h4>
                    <p style={{ fontSize: '12px', color: 'hsl(var(--muted-foreground))' }}>
                      {item.excerpt?.substring(0, 60)}...
                    </p>
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
