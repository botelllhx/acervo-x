import React, { useEffect, useState } from 'react';
import { FileImage, Search, Filter, ChevronLeft, ChevronRight, Edit, Link2, Unlink, CheckCircle2 } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function Items() {
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({
    collection: '',
    orderby: 'date',
    order: 'DESC'
  });
  const [collections, setCollections] = useState([]);
  const [linkingItem, setLinkingItem] = useState(null);
  const [linkingLoading, setLinkingLoading] = useState(null);
  const { showToast } = useToast();

  useEffect(() => {
    fetch('/wp-json/acervox/v1/collections')
      .then(res => res.json())
      .then(data => {
        if (data.collections) {
          setCollections(data.collections);
        }
      });

    // Listener para filtrar por coleção
    const handleFilter = (e) => {
      setFilters({ ...filters, collection: e.detail.collectionId });
      setPage(1);
    };
    window.addEventListener('acervox-filter-collection', handleFilter);
    return () => window.removeEventListener('acervox-filter-collection', handleFilter);
  }, []);

  const fetchItems = async (currentPage = 1, searchTerm = '', currentFilters = filters) => {
    setLoading(true);

    try {
      const params = new URLSearchParams({
        page: currentPage,
        per_page: 12,
        orderby: currentFilters.orderby,
        order: currentFilters.order,
      });

      if (searchTerm) {
        params.append('search', searchTerm);
      }

      if (currentFilters.collection) {
        params.append('collection', currentFilters.collection);
      }

      const response = await fetch(`/wp-json/acervox/v1/items?${params}`);
      const data = await response.json();

      setItems(data.items || []);
      setTotalPages(data.pages || 1);
    } catch (error) {
      console.error('Erro ao carregar itens', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchItems(page, search, filters);
  }, [page]);

  const handleSearch = (e) => {
    const value = e.target.value;
    setSearch(value);
    if (!value) {
      setPage(1);
      fetchItems(1, '', filters);
    }
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    fetchItems(1, search, filters);
  };

  const handleFilterChange = (key, value) => {
    const newFilters = { ...filters, [key]: value };
    setFilters(newFilters);
    setPage(1);
    fetchItems(1, search, newFilters);
  };

  const handleEdit = (id) => {
    window.open(`/wp-admin/post.php?post=${id}&action=edit`, '_blank');
  };

  const handleLinkCollection = async (itemId, collectionId) => {
    if (!collectionId || collectionId === '') {
      showToast('Por favor, selecione uma coleção', 'warning');
      return;
    }

    setLinkingLoading(itemId);

    try {
      const response = await fetch(`/wp-json/acervox/v1/items/${itemId}/link-collection`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify({
          collection_id: collectionId
        })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        const collectionTitle = data.collection_title || collections.find(c => c.id == collectionId)?.title || 'coleção';
        showToast(`Item vinculado à coleção "${collectionTitle}" com sucesso!`, 'success');
        await fetchItems(page, search, filters);
        setLinkingItem(null);
      } else {
        const errorMessage = data.message || data.error?.message || 'Erro ao vincular item à coleção';
        showToast(errorMessage, 'error');
      }
    } catch (error) {
      console.error('Erro ao atrelar coleção', error);
      showToast('Erro ao vincular item à coleção. Tente novamente.', 'error');
    } finally {
      setLinkingLoading(null);
    }
  };

  const handleUnlinkCollection = async (itemId) => {
    setLinkingLoading(itemId);

    try {
      const response = await fetch(`/wp-json/acervox/v1/items/${itemId}/unlink-collection`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        }
      });

      const data = await response.json();

      if (response.ok && data.success) {
        showToast('Item desvinculado da coleção com sucesso!', 'success');
        await fetchItems(page, search, filters);
        setLinkingItem(null);
      } else {
        const errorMessage = data.message || data.error?.message || 'Erro ao desvincular item da coleção';
        showToast(errorMessage, 'error');
      }
    } catch (error) {
      console.error('Erro ao desatrelar coleção', error);
      showToast('Erro ao desvincular item da coleção. Tente novamente.', 'error');
    } finally {
      setLinkingLoading(null);
    }
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Itens do Acervo</h1>
      </div>
      <div className="acervox-content">
        {/* Filtros */}
        <Card style={{ marginBottom: '24px' }}>
          <CardHeader>
            <CardTitle style={{ fontSize: '16px' }}>Filtros</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSearchSubmit} style={{ display: 'flex', gap: '12px', marginBottom: '16px' }}>
              <div style={{ flex: 1, position: 'relative' }}>
                <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'hsl(var(--muted-foreground))' }} />
                <Input
                  type="text"
                  placeholder="Buscar itens..."
                  value={search}
                  onChange={handleSearch}
                  style={{ paddingLeft: '40px' }}
                />
              </div>
              <Button type="submit">Buscar</Button>
            </form>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '12px' }}>
              <div className="acervox-form-group" style={{ marginBottom: 0 }}>
                <label className="acervox-form-label">Coleção</label>
                <Select
                  value={filters.collection}
                  onChange={(e) => handleFilterChange('collection', e.target.value)}
                >
                  <option value="">Todas as coleções</option>
                  {collections.map(col => (
                    <option key={col.id} value={col.id}>{col.title}</option>
                  ))}
                </Select>
              </div>

              <div className="acervox-form-group" style={{ marginBottom: 0 }}>
                <label className="acervox-form-label">Ordenar por</label>
                <Select
                  value={filters.orderby}
                  onChange={(e) => handleFilterChange('orderby', e.target.value)}
                >
                  <option value="date">Data</option>
                  <option value="title">Título</option>
                  <option value="modified">Modificado</option>
                </Select>
              </div>

              <div className="acervox-form-group" style={{ marginBottom: 0 }}>
                <label className="acervox-form-label">Ordem</label>
                <Select
                  value={filters.order}
                  onChange={(e) => handleFilterChange('order', e.target.value)}
                >
                  <option value="DESC">Decrescente</option>
                  <option value="ASC">Crescente</option>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Grid de Itens */}
        {loading ? (
          <div className="acervox-loading">
            <div className="acervox-spinner"></div>
          </div>
        ) : items.length === 0 ? (
          <Card>
            <CardContent>
              <div className="acervox-empty">
                <div className="acervox-empty-icon">
                  <FileImage size={48} />
                </div>
                <div className="acervox-empty-title">Nenhum item encontrado</div>
                <div className="acervox-empty-description">
                  {search ? 'Tente uma busca diferente ou ajuste os filtros.' : 'Adicione itens ao acervo para começar.'}
                </div>
              </div>
            </CardContent>
          </Card>
        ) : (
          <>
            <div className="acervox-grid acervox-grid-cols-4">
              {items.map(item => (
                <Card key={item.id} style={{ overflow: 'hidden', padding: 0 }}>
                  {item.thumbnails?.medium && (
                    <div style={{ width: '100%', paddingTop: '75%', position: 'relative', overflow: 'hidden', background: 'hsl(var(--muted))' }}>
                      <img
                        src={item.thumbnails.medium}
                        alt={item.title}
                        style={{
                          position: 'absolute',
                          top: 0,
                          left: 0,
                          width: '100%',
                          height: '100%',
                          objectFit: 'cover'
                        }}
                        loading="lazy"
                      />
                    </div>
                  )}
                  <CardContent style={{ padding: '16px' }}>
                    <h3 style={{ fontSize: '14px', fontWeight: 600, marginBottom: '8px', lineHeight: '1.4' }}>
                      {item.title}
                    </h3>
                    {item.excerpt && (
                      <p style={{ fontSize: '12px', color: 'hsl(var(--muted-foreground))', lineHeight: '1.5', marginBottom: '12px' }}>
                        {item.excerpt.substring(0, 80)}...
                      </p>
                    )}
                    <div style={{ marginBottom: '12px', display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                      {item.collection_id ? (
                        <span className="acervox-collection-badge">
                          <CheckCircle2 size={12} />
                          {collections.find(c => c.id == item.collection_id)?.title || 'Coleção desconhecida'}
                        </span>
                      ) : (
                        <span className="acervox-collection-badge no-collection">
                          Sem coleção
                        </span>
                      )}
                    </div>
                    <div style={{ display: 'flex', gap: '8px', flexDirection: 'column' }}>
                      <Button variant="outline" size="sm" style={{ width: '100%' }} onClick={() => handleEdit(item.id)}>
                        <Edit size={14} />
                        Editar
                      </Button>
                      {linkingItem === item.id ? (
                        <div style={{ display: 'flex', gap: '4px', alignItems: 'center', flexDirection: 'column' }}>
                          <Select
                            style={{ width: '100%', fontSize: '13px', height: '32px', padding: '6px 12px' }}
                            value={item.collection_id ? String(item.collection_id) : ''}
                            onChange={(e) => {
                              const selectedValue = e.target.value;
                              if (selectedValue) {
                                handleLinkCollection(item.id, selectedValue);
                              } else {
                                // Se selecionou "Selecione uma coleção...", desvincular
                                handleUnlinkCollection(item.id);
                              }
                            }}
                            disabled={linkingLoading === item.id}
                          >
                            <option value="">Selecione uma coleção...</option>
                            {collections.map(col => (
                              <option key={col.id} value={String(col.id)}>{col.title}</option>
                            ))}
                          </Select>
                          <div style={{ display: 'flex', gap: '4px', width: '100%' }}>
                            {item.collection_id && (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleUnlinkCollection(item.id)}
                                disabled={linkingLoading === item.id}
                                style={{ flex: 1, fontSize: '12px' }}
                              >
                                <Unlink size={12} />
                                Desvincular
                              </Button>
                            )}
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => {
                                setLinkingItem(null);
                                setLinkingLoading(null);
                              }}
                              disabled={linkingLoading === item.id}
                              style={{ fontSize: '12px' }}
                            >
                              Cancelar
                            </Button>
                          </div>
                          {linkingLoading === item.id && (
                            <div style={{ fontSize: '11px', color: 'hsl(var(--muted-foreground))', textAlign: 'center', width: '100%' }}>
                              Salvando...
                            </div>
                          )}
                        </div>
                      ) : (
                        <Button
                          variant="outline"
                          size="sm"
                          style={{ width: '100%' }}
                          onClick={() => setLinkingItem(item.id)}
                        >
                          <Link2 size={14} />
                          {item.collection_id ? 'Alterar Coleção' : 'Atrelar Coleção'}
                        </Button>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>

            {/* Paginação */}
            {totalPages > 1 && (
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', marginTop: '32px' }}>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page === 1}
                  onClick={() => setPage(page - 1)}
                >
                  <ChevronLeft size={16} />
                  Anterior
                </Button>

                <span style={{ padding: '0 16px', fontSize: '14px', color: 'hsl(var(--muted-foreground))' }}>
                  Página {page} de {totalPages}
                </span>

                <Button
                  variant="outline"
                  size="sm"
                  disabled={page === totalPages}
                  onClick={() => setPage(page + 1)}
                >
                  Próxima
                  <ChevronRight size={16} />
                </Button>
              </div>
            )}
          </>
        )}
      </div>
    </>
  );
}
