import React, { useEffect, useState } from 'react';
import { Archive, Plus, Edit, FileImage, Trash2, Search, Filter, Calendar, User, Database, ChevronLeft, ChevronRight } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';

export default function Collections() {
  const [collections, setCollections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  useEffect(() => {
    loadCollections();
  }, [page, searchTerm]);

  const loadCollections = () => {
    setLoading(true);
    const params = new URLSearchParams({
      per_page: 20,
      page: page,
      search: searchTerm
    });

    fetch(`/wp-json/acervox/v1/collections?${params}`)
      .then(res => res.json())
      .then(data => {
        if (data.collections) {
          // Buscar contagem de itens para cada coleção
          const collectionsWithCounts = data.collections.map(col => {
            return fetch(`/wp-json/acervox/v1/items?collection=${col.id}&per_page=1`)
              .then(res => res.json())
              .then(itemsData => ({
                ...col,
                items_count: itemsData.total || 0
              }))
              .catch(() => ({ ...col, items_count: 0 }));
          });

          Promise.all(collectionsWithCounts).then(results => {
            setCollections(results);
            setTotalItems(data.total || 0);
            setTotalPages(Math.ceil((data.total || 0) / 20));
          });
        }
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  };

  const handleNewCollection = () => {
    window.open('/wp-admin/post-new.php?post_type=acervox_collection', '_blank');
  };

  const handleEdit = (id) => {
    window.open(`/wp-admin/post.php?post=${id}&action=edit`, '_blank');
  };

  const handleViewItems = (id) => {
    window.open(`/wp-admin/edit.php?post_type=acervox_item&acervox_collection=${id}`, '_blank');
  };

  const handleDelete = (id) => {
    if (confirm('Tem certeza que deseja excluir esta coleção? Todos os itens vinculados serão mantidos, mas perderão a associação com a coleção.')) {
      fetch(`/wp-json/wp/v2/acervox_collection/${id}`, {
        method: 'DELETE',
        headers: {
          'X-WP-Nonce': AcervoX?.nonce || ''
        }
      })
        .then(() => {
          loadCollections();
        })
        .catch(console.error);
    }
  };

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
    setPage(1);
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Coleções</h1>
        <Button onClick={handleNewCollection}>
          <Plus size={16} />
          Nova Coleção
        </Button>
      </div>
      <div className="acervox-content">
        <Card style={{ marginBottom: '24px' }}>
          <CardHeader>
            <CardTitle style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Filter size={18} />
              Filtros e Busca
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div style={{ display: 'flex', gap: '16px', alignItems: 'flex-end' }}>
              <div className="acervox-form-group" style={{ flex: 1 }}>
                <label className="acervox-form-label">Buscar coleções</label>
                <div style={{ position: 'relative' }}>
                  <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'hsl(var(--muted-foreground))', pointerEvents: 'none' }} />
                  <Input
                    type="text"
                    placeholder="Digite o nome da coleção..."
                    value={searchTerm}
                    onChange={handleSearch}
                    style={{ paddingLeft: '40px' }}
                  />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <CardTitle>Lista de Coleções</CardTitle>
                <CardDescription>
                  {totalItems} {totalItems === 1 ? 'coleção encontrada' : 'coleções encontradas'}
                </CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="acervox-loading">
                <div className="acervox-spinner"></div>
              </div>
            ) : collections.length === 0 ? (
              <div className="acervox-empty">
                <div className="acervox-empty-icon">
                  <Archive size={48} />
                </div>
                <div className="acervox-empty-title">
                  {searchTerm ? 'Nenhuma coleção encontrada' : 'Nenhuma coleção encontrada'}
                </div>
                <div className="acervox-empty-description">
                  {searchTerm 
                    ? 'Tente ajustar os termos de busca'
                    : 'Crie uma coleção para começar a organizar seu acervo'}
                </div>
                {!searchTerm && (
                  <Button onClick={handleNewCollection} style={{ marginTop: '16px' }}>
                    <Plus size={16} />
                    Criar Primeira Coleção
                  </Button>
                )}
              </div>
            ) : (
              <>
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ borderBottom: '2px solid hsl(var(--border))' }}>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Nome</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Descrição</th>
                        <th style={{ padding: '12px', textAlign: 'center', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Itens</th>
                        <th style={{ padding: '12px', textAlign: 'center', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Metadados</th>
                        <th style={{ padding: '12px', textAlign: 'left', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Modificado</th>
                        <th style={{ padding: '12px', textAlign: 'center', fontWeight: 600, fontSize: '13px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>Ações</th>
                      </tr>
                    </thead>
                    <tbody>
                      {collections.map((col) => {
                        const collectionPost = col; // Assumindo que temos acesso ao post
                        const modifiedDate = collectionPost.modified || collectionPost.date;
                        return (
                          <tr 
                            key={col.id} 
                            style={{ 
                              borderBottom: '1px solid hsl(var(--border))', 
                              transition: 'background 0.2s' 
                            }} 
                            onMouseEnter={(e) => e.currentTarget.style.background = 'hsl(var(--muted))'} 
                            onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}
                          >
                            <td style={{ padding: '12px', fontSize: '14px', fontWeight: 500 }}>
                              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <Archive size={16} style={{ color: 'hsl(var(--muted-foreground))' }} />
                                {col.title}
                              </div>
                            </td>
                            <td style={{ padding: '12px', fontSize: '14px', color: 'hsl(var(--muted-foreground))', maxWidth: '300px' }}>
                              <div style={{ 
                                overflow: 'hidden', 
                                textOverflow: 'ellipsis', 
                                whiteSpace: 'nowrap' 
                              }}>
                                {col.description || 'Sem descrição'}
                              </div>
                            </td>
                            <td style={{ padding: '12px', textAlign: 'center', fontSize: '14px' }}>
                              <span style={{ 
                                display: 'inline-flex', 
                                alignItems: 'center', 
                                gap: '4px',
                                padding: '4px 12px',
                                background: 'hsl(var(--muted))',
                                borderRadius: '12px',
                                fontWeight: 500
                              }}>
                                <FileImage size={14} />
                                {col.items_count || 0}
                              </span>
                            </td>
                            <td style={{ padding: '12px', textAlign: 'center', fontSize: '14px' }}>
                              <span style={{ 
                                display: 'inline-flex', 
                                alignItems: 'center', 
                                gap: '4px',
                                padding: '4px 12px',
                                background: 'hsl(var(--muted))',
                                borderRadius: '12px',
                                fontWeight: 500
                              }}>
                                <Database size={14} />
                                {col.fields_count || 0}
                              </span>
                            </td>
                            <td style={{ padding: '12px', fontSize: '13px', color: 'hsl(var(--muted-foreground))' }}>
                              <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                                <Calendar size={14} />
                                {modifiedDate ? new Date(modifiedDate).toLocaleDateString('pt-BR') : 'N/A'}
                              </span>
                            </td>
                            <td style={{ padding: '12px' }}>
                              <div style={{ display: 'flex', gap: '8px', justifyContent: 'center' }}>
                                <Button
                                  variant="ghost"
                                  size="icon"
                                  onClick={() => handleEdit(col.id)}
                                  style={{ padding: '6px' }}
                                  title="Editar"
                                >
                                  <Edit size={16} />
                                </Button>
                                <Button
                                  variant="ghost"
                                  size="icon"
                                  onClick={() => handleViewItems(col.id)}
                                  style={{ padding: '6px' }}
                                  title="Ver Itens"
                                >
                                  <FileImage size={16} />
                                </Button>
                                <Button
                                  variant="ghost"
                                  size="icon"
                                  onClick={() => handleDelete(col.id)}
                                  style={{ padding: '6px', color: 'hsl(var(--destructive))' }}
                                  title="Excluir"
                                >
                                  <Trash2 size={16} />
                                </Button>
                              </div>
                            </td>
                          </tr>
                        );
                      })}
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
                      <ChevronLeft size={16} />
                      Anterior
                    </Button>
                    <span style={{ fontSize: '14px', color: 'hsl(var(--muted-foreground))', padding: '0 16px' }}>
                      Página {page} de {totalPages}
                    </span>
                    <Button
                      variant="outline"
                      onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                      disabled={page === totalPages}
                    >
                      Próxima
                      <ChevronRight size={16} />
                    </Button>
                  </div>
                )}
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}
