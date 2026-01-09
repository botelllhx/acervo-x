import React, { useEffect, useState } from 'react';
import { Archive, Plus, Edit, MoreVertical, FileImage, Trash2 } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';

export default function Collections() {
  const [collections, setCollections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showMenu, setShowMenu] = useState(null);

  useEffect(() => {
    loadCollections();
  }, []);

  const loadCollections = () => {
    fetch('/wp-json/acervox/v1/collections')
      .then(res => res.json())
      .then(data => {
        if (data.collections) {
          setCollections(data.collections);
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
    // Mudar para aba de itens e filtrar por coleção
    const event = new CustomEvent('acervox-filter-collection', { detail: { collectionId: id } });
    window.dispatchEvent(event);
    // Também pode abrir em nova aba
    window.open(`/wp-admin/edit.php?post_type=acervox_item&acervox_collection=${id}`, '_blank');
  };

  const handleDelete = (id) => {
    if (confirm('Tem certeza que deseja excluir esta coleção?')) {
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
        {loading ? (
          <div className="acervox-loading">
            <div className="acervox-spinner"></div>
          </div>
        ) : collections.length === 0 ? (
          <Card>
            <CardContent>
              <div className="acervox-empty">
                <div className="acervox-empty-icon">
                  <Archive size={48} />
                </div>
                <div className="acervox-empty-title">Nenhuma coleção encontrada</div>
                <div className="acervox-empty-description">
                  Crie uma coleção para começar a organizar seu acervo
                </div>
                <Button onClick={handleNewCollection} style={{ marginTop: '16px' }}>
                  <Plus size={16} />
                  Criar Primeira Coleção
                </Button>
              </div>
            </CardContent>
          </Card>
        ) : (
          <div className="acervox-grid acervox-grid-cols-3">
            {collections.map(col => (
              <Card key={col.id}>
                <CardHeader>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', position: 'relative' }}>
                    <div>
                      <CardTitle style={{ fontSize: '18px', marginBottom: '4px' }}>
                        {col.title}
                      </CardTitle>
                      <CardDescription>
                        {col.fields_count || 0} {col.fields_count === 1 ? 'campo' : 'campos'} de metadados
                      </CardDescription>
                    </div>
                    <div style={{ position: 'relative' }}>
                      <Button 
                        variant="ghost" 
                        size="icon"
                        onClick={() => setShowMenu(showMenu === col.id ? null : col.id)}
                      >
                        <MoreVertical size={16} />
                      </Button>
                      {showMenu === col.id && (
                        <div style={{
                          position: 'absolute',
                          right: 0,
                          top: '100%',
                          background: 'white',
                          border: '1px solid hsl(var(--border))',
                          borderRadius: '6px',
                          boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                          zIndex: 100,
                          minWidth: '150px',
                          marginTop: '4px'
                        }}>
                          <button
                            onClick={() => { handleEdit(col.id); setShowMenu(null); }}
                            style={{
                              width: '100%',
                              padding: '10px 16px',
                              textAlign: 'left',
                              background: 'transparent',
                              border: 'none',
                              cursor: 'pointer',
                              fontSize: '14px',
                              display: 'flex',
                              alignItems: 'center',
                              gap: '8px',
                              color: 'hsl(var(--foreground))'
                            }}
                            onMouseEnter={(e) => e.target.style.background = 'hsl(var(--accent))'}
                            onMouseLeave={(e) => e.target.style.background = 'transparent'}
                          >
                            <Edit size={14} />
                            Editar
                          </button>
                          <button
                            onClick={() => { handleViewItems(col.id); setShowMenu(null); }}
                            style={{
                              width: '100%',
                              padding: '10px 16px',
                              textAlign: 'left',
                              background: 'transparent',
                              border: 'none',
                              cursor: 'pointer',
                              fontSize: '14px',
                              display: 'flex',
                              alignItems: 'center',
                              gap: '8px',
                              color: 'hsl(var(--foreground))',
                              borderTop: '1px solid hsl(var(--border))'
                            }}
                            onMouseEnter={(e) => e.target.style.background = 'hsl(var(--accent))'}
                            onMouseLeave={(e) => e.target.style.background = 'transparent'}
                          >
                            <FileImage size={14} />
                            Ver Itens
                          </button>
                          <button
                            onClick={() => { handleDelete(col.id); setShowMenu(null); }}
                            style={{
                              width: '100%',
                              padding: '10px 16px',
                              textAlign: 'left',
                              background: 'transparent',
                              border: 'none',
                              cursor: 'pointer',
                              fontSize: '14px',
                              display: 'flex',
                              alignItems: 'center',
                              gap: '8px',
                              color: 'hsl(var(--destructive))',
                              borderTop: '1px solid hsl(var(--border))'
                            }}
                            onMouseEnter={(e) => e.target.style.background = 'hsl(var(--accent))'}
                            onMouseLeave={(e) => e.target.style.background = 'transparent'}
                          >
                            <Trash2 size={14} />
                            Excluir
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div style={{ display: 'flex', gap: '8px' }}>
                    <Button variant="outline" size="sm" style={{ flex: 1 }} onClick={() => handleEdit(col.id)}>
                      <Edit size={14} />
                      Editar
                    </Button>
                    <Button variant="outline" size="sm" style={{ flex: 1 }} onClick={() => handleViewItems(col.id)}>
                      <FileImage size={14} />
                      Ver Itens
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>
    </>
  );
}
