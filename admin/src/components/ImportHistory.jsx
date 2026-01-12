import React, { useState, useEffect } from 'react';
import { History, FileText, Download, XCircle, CheckCircle2, AlertCircle, Trash2, Eye, Calendar, User, Archive, Filter } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Select } from './ui/Select';
import { Input } from './ui/Input';

export default function ImportHistory() {
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [filters, setFilters] = useState({
    import_type: '',
    status: '',
    collection_id: ''
  });
  const [selectedItem, setSelectedItem] = useState(null);
  const [collections, setCollections] = useState([]);

  useEffect(() => {
    loadCollections();
  }, []);

  useEffect(() => {
    loadHistory();
  }, [page, filters]);

  const loadCollections = () => {
    fetch('/wp-json/acervox/v1/collections')
      .then(res => res.json())
      .then(data => {
        if (data.collections) {
          setCollections(data.collections);
        }
      })
      .catch(console.error);
  };

  const loadHistory = () => {
    setLoading(true);
    const params = new URLSearchParams({
      page: page,
      per_page: 20,
      ...filters
    });

    fetch(`/wp-json/acervox/v1/import-history?${params}`, {
      headers: {
        'X-WP-Nonce': AcervoX?.nonce || ''
      }
    })
      .then(res => res.json())
      .then(data => {
        if (data.items) {
          setHistory(data.items);
          setTotalPages(data.pages || 1);
        }
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  };

  const handleDelete = async (id) => {
    if (!confirm('Tem certeza que deseja deletar este registro do histórico?')) {
      return;
    }

    try {
      const response = await fetch(`/wp-json/acervox/v1/import-history/${id}`, {
        method: 'DELETE',
        headers: {
          'X-WP-Nonce': AcervoX?.nonce || ''
        }
      });

      if (response.ok) {
        loadHistory();
      } else {
        alert('Erro ao deletar histórico');
      }
    } catch (error) {
      alert('Erro ao deletar: ' + error.message);
    }
  };

  const handleViewDetails = async (id) => {
    try {
      const response = await fetch(`/wp-json/acervox/v1/import-history/${id}`, {
        headers: {
          'X-WP-Nonce': AcervoX?.nonce || ''
        }
      });

      const data = await response.json();
      if (response.ok) {
        setSelectedItem(data);
      }
    } catch (error) {
      alert('Erro ao carregar detalhes: ' + error.message);
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'completed':
        return <CheckCircle2 size={16} style={{ color: 'hsl(var(--primary))' }} />;
      case 'failed':
        return <XCircle size={16} style={{ color: 'hsl(var(--destructive))' }} />;
      case 'processing':
        return <AlertCircle size={16} style={{ color: 'hsl(var(--muted-foreground))' }} />;
      default:
        return <AlertCircle size={16} />;
    }
  };

  const getStatusLabel = (status) => {
    switch (status) {
      case 'completed':
        return 'Concluído';
      case 'failed':
        return 'Falhou';
      case 'processing':
        return 'Processando';
      default:
        return status;
    }
  };

  const getTypeLabel = (type) => {
    switch (type) {
      case 'csv':
        return 'CSV';
      case 'external':
        return 'Sistema Externo';
      default:
        return type;
    }
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Histórico de Importações</h1>
      </div>
      <div className="acervox-content">
        <Card style={{ marginBottom: '24px' }}>
          <CardHeader>
            <CardTitle style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Filter size={18} />
              Filtros
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px' }}>
              <div className="acervox-form-group">
                <label className="acervox-form-label">Tipo de Importação</label>
                <Select
                  value={filters.import_type}
                  onChange={(e) => setFilters({ ...filters, import_type: e.target.value })}
                >
                  <option value="">Todos</option>
                  <option value="csv">CSV</option>
                  <option value="external">Sistema Externo</option>
                </Select>
              </div>
              <div className="acervox-form-group">
                <label className="acervox-form-label">Status</label>
                <Select
                  value={filters.status}
                  onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                >
                  <option value="">Todos</option>
                  <option value="completed">Concluído</option>
                  <option value="failed">Falhou</option>
                  <option value="processing">Processando</option>
                </Select>
              </div>
              <div className="acervox-form-group">
                <label className="acervox-form-label">Coleção</label>
                <Select
                  value={filters.collection_id}
                  onChange={(e) => setFilters({ ...filters, collection_id: e.target.value })}
                >
                  <option value="">Todas</option>
                  {collections.map(col => (
                    <option key={col.id} value={col.id}>
                      {col.title}
                    </option>
                  ))}
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Registros de Importação</CardTitle>
            <CardDescription>
              Histórico completo de todas as importações realizadas
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="acervox-loading">
                <div className="acervox-spinner"></div>
              </div>
            ) : history.length === 0 ? (
              <div className="acervox-empty">
                <div className="acervox-empty-icon">
                  <History size={48} />
                </div>
                <div className="acervox-empty-title">Nenhum histórico encontrado</div>
                <div className="acervox-empty-description">
                  As importações realizadas aparecerão aqui
                </div>
              </div>
            ) : (
              <>
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ borderBottom: '2px solid hsl(var(--border))' }}>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>ID</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Tipo</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Coleção</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Itens</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Status</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Data</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Ações</th>
                      </tr>
                    </thead>
                    <tbody>
                      {history.map((item) => (
                        <tr key={item.id} style={{ borderBottom: '1px solid hsl(var(--border))', transition: 'background 0.2s' }} onMouseEnter={(e) => e.currentTarget.style.background = 'hsl(var(--muted))'} onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}>
                          <td style={{ padding: '12px', fontSize: '14px' }}>#{item.id}</td>
                          <td style={{ padding: '12px', fontSize: '14px' }}>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                              <FileText size={14} />
                              {getTypeLabel(item.import_type)}
                            </span>
                          </td>
                          <td style={{ padding: '12px', fontSize: '14px' }}>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                              <Archive size={14} />
                              {item.collection_title || 'N/A'}
                            </span>
                          </td>
                          <td style={{ padding: '12px', fontSize: '14px' }}>
                            {item.imported_items} / {item.total_items}
                            {item.failed_items > 0 && (
                              <span style={{ marginLeft: '8px', color: 'hsl(var(--destructive))', fontSize: '12px' }}>
                                ({item.failed_items} falhas)
                              </span>
                            )}
                          </td>
                          <td style={{ padding: '12px', fontSize: '14px' }}>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                              {getStatusIcon(item.status)}
                              {getStatusLabel(item.status)}
                            </span>
                          </td>
                          <td style={{ padding: '12px', fontSize: '14px' }}>
                            <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                              <Calendar size={14} />
                              {item.started_at_formatted}
                            </span>
                          </td>
                          <td style={{ padding: '12px' }}>
                            <div style={{ display: 'flex', gap: '8px' }}>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => handleViewDetails(item.id)}
                                style={{ padding: '6px' }}
                              >
                                <Eye size={16} />
                              </Button>
                              <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => handleDelete(item.id)}
                                style={{ padding: '6px', color: 'hsl(var(--destructive))' }}
                              >
                                <Trash2 size={16} />
                              </Button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {totalPages > 1 && (
                  <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '8px', marginTop: '24px' }}>
                    <Button
                      variant="outline"
                      onClick={() => setPage(p => Math.max(1, p - 1))}
                      disabled={page === 1}
                    >
                      Anterior
                    </Button>
                    <span style={{ fontSize: '14px', color: 'hsl(var(--muted-foreground))' }}>
                      Página {page} de {totalPages}
                    </span>
                    <Button
                      variant="outline"
                      onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                      disabled={page === totalPages}
                    >
                      Próxima
                    </Button>
                  </div>
                )}
              </>
            )}
          </CardContent>
        </Card>

        {selectedItem && (
          <Card style={{ marginTop: '24px' }}>
            <CardHeader>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <CardTitle>Detalhes da Importação #{selectedItem.id}</CardTitle>
                <Button variant="ghost" size="icon" onClick={() => setSelectedItem(null)}>
                  <XCircle size={18} />
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '16px', marginBottom: '24px' }}>
                <div>
                  <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '4px' }}>Tipo</div>
                  <div style={{ fontSize: '16px', fontWeight: 500 }}>{getTypeLabel(selectedItem.import_type)}</div>
                </div>
                <div>
                  <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '4px' }}>Status</div>
                  <div style={{ fontSize: '16px', fontWeight: 500, display: 'flex', alignItems: 'center', gap: '6px' }}>
                    {getStatusIcon(selectedItem.status)}
                    {getStatusLabel(selectedItem.status)}
                  </div>
                </div>
                <div>
                  <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '4px' }}>Coleção</div>
                  <div style={{ fontSize: '16px', fontWeight: 500 }}>{selectedItem.collection_title}</div>
                </div>
                <div>
                  <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '4px' }}>Itens</div>
                  <div style={{ fontSize: '16px', fontWeight: 500 }}>
                    {selectedItem.imported_items} / {selectedItem.total_items}
                  </div>
                </div>
                <div>
                  <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '4px' }}>Iniciado em</div>
                  <div style={{ fontSize: '16px', fontWeight: 500 }}>{selectedItem.started_at_formatted}</div>
                </div>
                {selectedItem.completed_at && (
                  <div>
                    <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '4px' }}>Concluído em</div>
                    <div style={{ fontSize: '16px', fontWeight: 500 }}>{selectedItem.completed_at_formatted}</div>
                    {selectedItem.duration && (
                      <div style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))', marginTop: '4px' }}>
                        Duração: {selectedItem.duration}
                      </div>
                    )}
                  </div>
                )}
                <div>
                  <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '4px' }}>Usuário</div>
                  <div style={{ fontSize: '16px', fontWeight: 500 }}>{selectedItem.user_name}</div>
                </div>
              </div>

              {selectedItem.error_message && (
                <div className="acervox-alert acervox-alert-error" style={{ marginBottom: '24px' }}>
                  <AlertCircle size={20} />
                  <div>
                    <strong>Erro:</strong>
                    <p style={{ margin: '8px 0 0 0', fontSize: '14px' }}>{selectedItem.error_message}</p>
                  </div>
                </div>
              )}

              {selectedItem.log_data && selectedItem.log_data.length > 0 && (
                <div>
                  <h3 style={{ fontSize: '16px', fontWeight: 600, marginBottom: '12px' }}>Log de Importação</h3>
                  <div style={{
                    maxHeight: '300px',
                    overflowY: 'auto',
                    padding: '16px',
                    background: 'hsl(var(--muted))',
                    borderRadius: '8px',
                    fontSize: '13px',
                    fontFamily: 'monospace',
                    lineHeight: '1.6'
                  }}>
                    {Array.isArray(selectedItem.log_data) ? (
                      selectedItem.log_data.map((log, idx) => (
                        <div key={idx} style={{ marginBottom: '4px' }}>{log}</div>
                      ))
                    ) : (
                      <div>{selectedItem.log_data}</div>
                    )}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        )}
      </div>
    </>
  );
}
