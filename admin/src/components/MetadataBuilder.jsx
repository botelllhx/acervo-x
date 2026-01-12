import React, { useState, useEffect } from 'react';
import { Database, Plus, Trash2, Save } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function MetadataBuilder() {
  const [fields, setFields] = useState([]);
  const [selectedCollection, setSelectedCollection] = useState('');
  const [collections, setCollections] = useState([]);
  const [saving, setSaving] = useState(false);
  const { showToast } = useToast();

  useEffect(() => {
    fetch('/wp-json/acervox/v1/collections')
      .then(res => res.json())
      .then(data => {
        if (data.collections) {
          setCollections(data.collections);
        }
      })
      .catch(console.error);
  }, []);

  useEffect(() => {
    if (selectedCollection) {
      // Buscar via nossa API customizada que já retorna os fields
      fetch(`/wp-json/acervox/v1/collections`)
        .then(res => res.json())
        .then(data => {
          if (data.collections) {
            const collection = data.collections.find(col => col.id == selectedCollection);
            if (collection && collection.fields) {
              setFields(Array.isArray(collection.fields) ? collection.fields : []);
            } else {
              setFields([]);
            }
          } else {
            setFields([]);
          }
        })
        .catch(() => setFields([]));
    } else {
      setFields([]);
    }
  }, [selectedCollection]);

  const addField = () => {
    setFields([
      ...fields,
      { label: '', key: '', type: 'text', required: false, options: [] }
    ]);
  };

  const updateField = (index, updates) => {
    const newFields = [...fields];
    newFields[index] = { ...newFields[index], ...updates };
    setFields(newFields);
  };

  const removeField = (index) => {
    setFields(fields.filter((_, i) => i !== index));
  };

  const saveFields = async () => {
    if (!selectedCollection) {
      showToast('Por favor, selecione uma coleção primeiro', 'warning');
      return;
    }

    setSaving(true);
    try {
      const response = await fetch(`/wp-json/wp/v2/acervox_collection/${selectedCollection}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify({
          meta: {
            _acervox_fields: JSON.stringify(fields)
          }
        })
      });

      if (response.ok) {
        showToast('Metadados salvos com sucesso!', 'success');
      } else {
        const data = await response.json();
        showToast(data.message || 'Erro ao salvar metadados', 'error');
      }
    } catch (error) {
      showToast('Erro ao salvar: ' + error.message, 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Builder de Metadados</h1>
      </div>
      <div className="acervox-content">
        <Card style={{ marginBottom: '24px' }}>
          <CardHeader>
            <CardTitle>Selecionar Coleção</CardTitle>
            <CardDescription>Escolha uma coleção para configurar seus metadados</CardDescription>
          </CardHeader>
          <CardContent>
            <Select
              value={selectedCollection}
              onChange={(e) => setSelectedCollection(e.target.value)}
              style={{ maxWidth: '400px' }}
            >
              <option value="">Selecione uma coleção</option>
              {collections.map(col => (
                <option key={col.id} value={col.id}>
                  {col.title}
                </option>
              ))}
            </Select>
          </CardContent>
        </Card>

        {selectedCollection && (
          <>
            <Card style={{ marginBottom: '24px', background: 'hsl(var(--muted))', border: '1px solid hsl(var(--border))' }}>
              <CardHeader>
                <CardTitle style={{ fontSize: '14px', textTransform: 'uppercase', letterSpacing: '0.5px', color: 'hsl(var(--muted-foreground))' }}>
                  📌 Metadados Padrão (Obrigatórios)
                </CardTitle>
                <CardDescription>
                  Estes campos são obrigatórios e não podem ser removidos
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  <div style={{ padding: '12px', background: 'white', borderRadius: '6px', border: '1px solid hsl(var(--border))' }}>
                    <strong style={{ display: 'block', marginBottom: '4px', color: 'hsl(var(--foreground))' }}>Coleção</strong>
                    <span style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))' }}>A coleção à qual o item pertence</span>
                  </div>
                  <div style={{ padding: '12px', background: 'white', borderRadius: '6px', border: '1px solid hsl(var(--border))' }}>
                    <strong style={{ display: 'block', marginBottom: '4px', color: 'hsl(var(--foreground))' }}>Título</strong>
                    <span style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))' }}>O título do item (puxado automaticamente do título do post)</span>
                  </div>
                  <div style={{ padding: '12px', background: 'white', borderRadius: '6px', border: '1px solid hsl(var(--border))' }}>
                    <strong style={{ display: 'block', marginBottom: '4px', color: 'hsl(var(--foreground))' }}>Descrição</strong>
                    <span style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))' }}>Descrição do item</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div>
                    <CardTitle>Campos de Metadados Personalizados</CardTitle>
                    <CardDescription>Configure os campos personalizados para esta coleção</CardDescription>
                  </div>
                  <div style={{ display: 'flex', gap: '8px' }}>
                    <Button variant="outline" onClick={addField}>
                      <Plus size={16} />
                      Adicionar Campo
                    </Button>
                    <Button onClick={saveFields} disabled={saving}>
                      <Save size={16} />
                      {saving ? 'Salvando...' : 'Salvar'}
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {fields.length === 0 ? (
                  <div className="acervox-empty">
                    <div className="acervox-empty-icon">
                      <Database size={48} />
                    </div>
                    <div className="acervox-empty-title">Nenhum campo personalizado configurado</div>
                    <div className="acervox-empty-description">
                      Adicione campos personalizados para coletar metadados adicionais
                    </div>
                    <Button variant="outline" onClick={addField} style={{ marginTop: '16px' }}>
                      <Plus size={16} />
                      Adicionar Primeiro Campo
                    </Button>
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                    {fields.map((field, index) => (
                      <Card key={index} style={{ border: '1px solid hsl(var(--border))' }}>
                        <CardContent style={{ padding: '20px' }}>
                          <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr auto', gap: '12px', alignItems: 'end' }}>
                            <div className="acervox-form-group" style={{ marginBottom: 0 }}>
                              <label className="acervox-form-label">Label</label>
                              <Input
                                type="text"
                                placeholder="Nome do campo"
                                value={field.label}
                                onChange={(e) => updateField(index, { 
                                  label: e.target.value, 
                                  key: field.key || e.target.value.toLowerCase().replace(/\s+/g, '_') 
                                })}
                              />
                            </div>

                            <div className="acervox-form-group" style={{ marginBottom: 0 }}>
                              <label className="acervox-form-label">Tipo</label>
                              <Select
                                value={field.type}
                                onChange={(e) => updateField(index, { type: e.target.value })}
                              >
                                <option value="text">Texto</option>
                                <option value="number">Número</option>
                                <option value="date">Data</option>
                                <option value="select">Select</option>
                                <option value="textarea">Textarea</option>
                              </Select>
                            </div>

                            <div className="acervox-form-group" style={{ marginBottom: 0 }}>
                              <label className="acervox-form-label">Obrigatório</label>
                              <Select
                                value={field.required ? 'yes' : 'no'}
                                onChange={(e) => updateField(index, { required: e.target.value === 'yes' })}
                              >
                                <option value="no">Não</option>
                                <option value="yes">Sim</option>
                              </Select>
                            </div>

                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => removeField(index)}
                              style={{ color: 'hsl(var(--destructive))' }}
                            >
                              <Trash2 size={16} />
                            </Button>
                          </div>

                          {field.type === 'select' && (
                            <div className="acervox-form-group" style={{ marginTop: '12px' }}>
                              <label className="acervox-form-label">Opções (separadas por vírgula)</label>
                              <Input
                                type="text"
                                placeholder="Opção 1, Opção 2, Opção 3"
                                value={Array.isArray(field.options) ? field.options.join(', ') : ''}
                                onChange={(e) => updateField(index, { 
                                  options: e.target.value.split(',').map(o => o.trim()).filter(o => o) 
                                })}
                              />
                            </div>
                          )}
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </>
        )}
      </div>
    </>
  );
}
