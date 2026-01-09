import React, { useState, useEffect } from 'react';
import { Database, Plus, Trash2, Save } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';

export default function MetadataBuilder() {
  const [fields, setFields] = useState([]);
  const [selectedCollection, setSelectedCollection] = useState('');
  const [collections, setCollections] = useState([]);
  const [saving, setSaving] = useState(false);

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
      fetch(`/wp-json/wp/v2/acervox_collection/${selectedCollection}`)
        .then(res => res.json())
        .then(data => {
          if (data.meta && data.meta._acervox_fields) {
            const fieldsData = Array.isArray(data.meta._acervox_fields) 
              ? data.meta._acervox_fields[0] 
              : data.meta._acervox_fields;
            try {
              const parsed = typeof fieldsData === 'string' ? JSON.parse(fieldsData) : fieldsData;
              setFields(Array.isArray(parsed) ? parsed : []);
            } catch {
              setFields([]);
            }
          } else {
            setFields([]);
          }
        })
        .catch(() => setFields([]));
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
      alert('Selecione uma coleção primeiro');
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
        alert('Metadados salvos com sucesso!');
      } else {
        alert('Erro ao salvar metadados');
      }
    } catch (error) {
      alert('Erro ao salvar: ' + error.message);
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
          <Card>
            <CardHeader>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                  <CardTitle>Campos de Metadados</CardTitle>
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
                  <div className="acervox-empty-title">Nenhum campo configurado</div>
                  <div className="acervox-empty-description">
                    Adicione campos para começar a coletar metadados
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
        )}
      </div>
    </>
  );
}
