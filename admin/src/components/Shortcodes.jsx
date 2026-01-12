import React, { useState, useEffect } from 'react';
import { Code, Plus, Trash2, Edit, Copy, Check } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function Shortcodes() {
  const [shortcodes, setShortcodes] = useState([]);
  const [collections, setCollections] = useState([]);
  const [editing, setEditing] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    collection: '',
    per_page: '12',
    layout: 'grid',
    columns: '3',
    filters: true,
    pagination: true,
    show_excerpt: true,
    show_meta: true,
  });
  const { showToast } = useToast();

  useEffect(() => {
    loadShortcodes();
    loadCollections();
  }, []);

  const loadShortcodes = () => {
    // Carregar shortcodes salvos do WordPress
    fetch('/wp-json/acervox/v1/shortcodes', {
      headers: {
        'X-WP-Nonce': AcervoX?.nonce || ''
      }
    })
      .then(r => r.json())
      .then(shortcodesData => {
        if (shortcodesData.shortcodes && Array.isArray(shortcodesData.shortcodes)) {
          setShortcodes(shortcodesData.shortcodes);
        } else {
          // Fallback para localStorage
          const saved = localStorage.getItem('acervox_shortcodes');
          if (saved) {
            try {
              setShortcodes(JSON.parse(saved));
            } catch (e) {
              setShortcodes([]);
            }
          }
        }
      })
      .catch(() => {
        // Fallback para localStorage
        const saved = localStorage.getItem('acervox_shortcodes');
        if (saved) {
          try {
            setShortcodes(JSON.parse(saved));
          } catch (e) {
            setShortcodes([]);
          }
        }
      });
  };

  const loadCollections = () => {
    fetch('/wp-json/acervox/v1/collections')
      .then(res => res.json())
      .then(data => {
        if (data.collections) {
          setCollections(data.collections);
        }
      });
  };

  const saveShortcode = () => {
    const newShortcode = {
      id: editing || Date.now().toString(),
      ...formData,
      code: `[acervox id="${editing || Date.now()}"${formData.collection ? ` collection="${formData.collection}"` : ''} per_page="${formData.per_page}" layout="${formData.layout}" columns="${formData.columns}" filters="${formData.filters}" pagination="${formData.pagination}"]`
    };

    let updated;
    if (editing) {
      updated = shortcodes.map(s => s.id === editing ? newShortcode : s);
    } else {
      updated = [...shortcodes, newShortcode];
    }

    setShortcodes(updated);
    localStorage.setItem('acervox_shortcodes', JSON.stringify(updated));
    
    // Salvar no WordPress
    fetch('/wp-json/acervox/v1/shortcodes', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': AcervoX?.nonce || ''
      },
      body: JSON.stringify({ shortcodes: updated })
    })
    .then(() => {
      showToast(editing ? 'Shortcode atualizado com sucesso!' : 'Shortcode criado com sucesso!', 'success');
    })
    .catch((error) => {
      showToast('Erro ao salvar shortcode: ' + error.message, 'error');
    });
    
    setShowForm(false);
    setEditing(null);
    setFormData({
      name: '',
      collection: '',
      per_page: '12',
      layout: 'grid',
      columns: '3',
      filters: true,
      pagination: true,
      show_excerpt: true,
      show_meta: true,
    });
  };

  const deleteShortcode = (id) => {
    const shortcode = shortcodes.find(s => s.id === id);
    const shortcodeName = shortcode ? (shortcode.name || 'este shortcode') : 'este shortcode';
    
    if (confirm(`Tem certeza que deseja excluir "${shortcodeName}"?`)) {
      const updated = shortcodes.filter(s => s.id !== id);
      setShortcodes(updated);
      localStorage.setItem('acervox_shortcodes', JSON.stringify(updated));
      
      // Salvar no WordPress
      fetch('/wp-json/acervox/v1/shortcodes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify({ shortcodes: updated })
      })
      .then(() => {
        showToast(`Shortcode "${shortcodeName}" excluído com sucesso!`, 'success');
      })
      .catch((error) => {
        showToast('Erro ao excluir shortcode: ' + error.message, 'error');
      });
    }
  };

  const editShortcode = (shortcode) => {
    setEditing(shortcode.id);
    setFormData({
      name: shortcode.name || '',
      collection: shortcode.collection || '',
      per_page: shortcode.per_page || '12',
      layout: shortcode.layout || 'grid',
      columns: shortcode.columns || '3',
      filters: shortcode.filters !== false,
      pagination: shortcode.pagination !== false,
      show_excerpt: shortcode.show_excerpt !== false,
      show_meta: shortcode.show_meta !== false,
    });
    setShowForm(true);
  };

  const copyCode = (code) => {
    navigator.clipboard.writeText(code);
    showToast('Código copiado para a área de transferência!', 'success', 2000);
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Shortcodes</h1>
        <Button onClick={() => { setShowForm(true); setEditing(null); setFormData({
          name: '', collection: '', per_page: '12', layout: 'grid', columns: '3',
          filters: true, pagination: true, show_excerpt: true, show_meta: true
        }); }}>
          <Plus size={16} />
          Novo Shortcode
        </Button>
      </div>
      <div className="acervox-content">
        {showForm && (
          <Card style={{ marginBottom: '24px' }}>
            <CardHeader>
              <CardTitle>{editing ? 'Editar' : 'Criar'} Shortcode</CardTitle>
              <CardDescription>Configure os parâmetros do shortcode</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="acervox-form-group">
                <label className="acervox-form-label">Nome (opcional)</label>
                <Input
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="Ex: Galeria Principal"
                />
              </div>

              <div className="acervox-form-group">
                <label className="acervox-form-label">Coleção</label>
                <Select
                  value={formData.collection}
                  onChange={(e) => setFormData({ ...formData, collection: e.target.value })}
                >
                  <option value="">Todas as coleções (seleção dinâmica)</option>
                  {collections.map(col => (
                    <option key={col.id} value={col.id}>{col.title}</option>
                  ))}
                </Select>
                <p style={{ fontSize: '12px', color: 'hsl(var(--muted-foreground))', marginTop: '4px' }}>
                  Se selecionada, o shortcode mostrará apenas esta coleção com filtros avançados
                </p>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '16px' }}>
                <div className="acervox-form-group">
                  <label className="acervox-form-label">Itens por página</label>
                  <Input
                    type="number"
                    value={formData.per_page}
                    onChange={(e) => setFormData({ ...formData, per_page: e.target.value })}
                  />
                </div>

                <div className="acervox-form-group">
                  <label className="acervox-form-label">Layout</label>
                  <Select
                    value={formData.layout}
                    onChange={(e) => setFormData({ ...formData, layout: e.target.value })}
                  >
                    <option value="grid">Grid</option>
                    <option value="masonry">Masonry</option>
                    <option value="list">Lista</option>
                  </Select>
                </div>

                <div className="acervox-form-group">
                  <label className="acervox-form-label">Colunas</label>
                  <Select
                    value={formData.columns}
                    onChange={(e) => setFormData({ ...formData, columns: e.target.value })}
                  >
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                  </Select>
                </div>
              </div>

              <div style={{ display: 'flex', gap: '12px', marginTop: '20px' }}>
                <Button onClick={saveShortcode}>
                  <Check size={16} />
                  Salvar
                </Button>
                <Button variant="outline" onClick={() => { setShowForm(false); setEditing(null); }}>
                  Cancelar
                </Button>
              </div>
            </CardContent>
          </Card>
        )}

        {shortcodes.length === 0 ? (
          <Card>
            <CardContent>
              <div className="acervox-empty">
                <div className="acervox-empty-icon">
                  <Code size={48} />
                </div>
                <div className="acervox-empty-title">Nenhum shortcode criado</div>
                <div className="acervox-empty-description">
                  Crie shortcodes personalizados para exibir seu acervo
                </div>
              </div>
            </CardContent>
          </Card>
        ) : (
          <div className="acervox-grid acervox-grid-cols-2">
            {shortcodes.map(shortcode => (
              <Card key={shortcode.id}>
                <CardHeader>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start' }}>
                    <div>
                      <CardTitle>{shortcode.name || 'Shortcode sem nome'}</CardTitle>
                      <CardDescription>
                        {shortcode.collection 
                          ? `Coleção: ${collections.find(c => c.id == shortcode.collection)?.title || 'N/A'}`
                          : 'Todas as coleções'}
                      </CardDescription>
                    </div>
                    <div style={{ display: 'flex', gap: '4px' }}>
                      <Button variant="ghost" size="icon" onClick={() => copyCode(shortcode.code)}>
                        <Copy size={16} />
                      </Button>
                      <Button variant="ghost" size="icon" onClick={() => editShortcode(shortcode)}>
                        <Edit size={16} />
                      </Button>
                      <Button variant="ghost" size="icon" onClick={() => deleteShortcode(shortcode.id)}>
                        <Trash2 size={16} />
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div style={{ background: 'hsl(var(--muted))', padding: '12px', borderRadius: '6px', fontFamily: 'monospace', fontSize: '12px', wordBreak: 'break-all' }}>
                    {shortcode.code}
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
