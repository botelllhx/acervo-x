import React, { useState, useEffect } from 'react';
import { Settings as SettingsIcon, Save, Image, Layout, Search, Filter, FileText, Info, Package } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';

export default function Settings() {
  const [settings, setSettings] = useState({
    items_per_page: 12,
    default_layout: 'grid',
    default_columns: 3,
    enable_search: true,
    enable_filters: true,
    enable_pagination: true,
    image_quality: 'large',
    lazy_load_images: true,
  });
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    fetch('/wp-json/acervox/v1/settings', {
      headers: {
        'X-WP-Nonce': AcervoX?.nonce || ''
      }
    })
      .then(res => res.json())
      .then(data => {
        if (data && !data.code) {
          setSettings(data);
        }
      })
      .catch(console.error);
  }, []);

  const handleChange = (key, value) => {
    setSettings(prev => ({ ...prev, [key]: value }));
    setSaved(false);
  };

  const handleSave = async () => {
    setSaving(true);
    setSaved(false);
    
    try {
      const response = await fetch('/wp-json/acervox/v1/settings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify(settings)
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        setSaved(true);
        setTimeout(() => setSaved(false), 3000);
      } else {
        alert('Erro ao salvar configurações');
      }
    } catch (error) {
      console.error('Erro ao salvar:', error);
      alert('Erro ao salvar configurações: ' + error.message);
    } finally {
      setSaving(false);
    }
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Configurações</h1>
      </div>
      <div className="acervox-content">
        <Card style={{ marginBottom: '24px' }}>
          <CardHeader>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <CardTitle>Configurações Gerais</CardTitle>
                <CardDescription>
                  Gerencie as configurações padrão do AcervoX
                </CardDescription>
              </div>
              <Button onClick={handleSave} disabled={saving} style={{ minWidth: '120px' }}>
                <Save size={16} />
                {saving ? 'Salvando...' : saved ? 'Salvo!' : 'Salvar'}
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
              {/* Exibição */}
              <div>
                <h3 style={{ fontSize: '16px', fontWeight: 600, marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Layout size={18} />
                  Exibição
                </h3>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px' }}>
                  <div className="acervox-form-group">
                    <label className="acervox-form-label">Itens por página</label>
                    <Input
                      type="number"
                      min="1"
                      max="100"
                      value={settings.items_per_page}
                      onChange={(e) => handleChange('items_per_page', parseInt(e.target.value) || 12)}
                    />
                  </div>
                  <div className="acervox-form-group">
                    <label className="acervox-form-label">Layout padrão</label>
                    <Select
                      value={settings.default_layout}
                      onChange={(e) => handleChange('default_layout', e.target.value)}
                    >
                      <option value="grid">Grid</option>
                      <option value="masonry">Masonry</option>
                      <option value="list">Lista</option>
                    </Select>
                  </div>
                  <div className="acervox-form-group">
                    <label className="acervox-form-label">Colunas padrão</label>
                    <Input
                      type="number"
                      min="1"
                      max="6"
                      value={settings.default_columns}
                      onChange={(e) => handleChange('default_columns', parseInt(e.target.value) || 3)}
                    />
                  </div>
                </div>
              </div>

              {/* Funcionalidades */}
              <div>
                <h3 style={{ fontSize: '16px', fontWeight: 600, marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Filter size={18} />
                  Funcionalidades
                </h3>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={settings.enable_search}
                      onChange={(e) => handleChange('enable_search', e.target.checked)}
                      style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                    />
                    <span style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <Search size={16} />
                      Habilitar busca
                    </span>
                  </label>
                  <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={settings.enable_filters}
                      onChange={(e) => handleChange('enable_filters', e.target.checked)}
                      style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                    />
                    <span style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <Filter size={16} />
                      Habilitar filtros
                    </span>
                  </label>
                  <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={settings.enable_pagination}
                      onChange={(e) => handleChange('enable_pagination', e.target.checked)}
                      style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                    />
                    <span style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <FileText size={16} />
                      Habilitar paginação
                    </span>
                  </label>
                </div>
              </div>

              {/* Imagens */}
              <div>
                <h3 style={{ fontSize: '16px', fontWeight: 600, marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Image size={18} />
                  Imagens
                </h3>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px' }}>
                  <div className="acervox-form-group">
                    <label className="acervox-form-label">Qualidade da imagem</label>
                    <Select
                      value={settings.image_quality}
                      onChange={(e) => handleChange('image_quality', e.target.value)}
                    >
                      <option value="thumbnail">Thumbnail</option>
                      <option value="medium">Média</option>
                      <option value="large">Grande</option>
                      <option value="full">Completa</option>
                    </Select>
                  </div>
                  <div className="acervox-form-group" style={{ display: 'flex', alignItems: 'flex-end' }}>
                    <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer', paddingBottom: '8px' }}>
                      <input
                        type="checkbox"
                        checked={settings.lazy_load_images}
                        onChange={(e) => handleChange('lazy_load_images', e.target.checked)}
                        style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                      />
                      <span>Carregamento preguiçoso (lazy load)</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card style={{ marginTop: '24px' }}>
          <CardHeader>
            <CardTitle style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Info size={18} />
              Informações do Plugin
            </CardTitle>
            <CardDescription>
              Detalhes sobre a versão e informações do AcervoX
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '24px' }}>
              <div>
                <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '8px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                  Versão do Plugin
                </div>
                <div style={{ fontSize: '18px', fontWeight: 700, color: 'hsl(var(--foreground))', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Package size={18} />
                  0.2.0
                </div>
              </div>
              <div>
                <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '8px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                  Versão do Banco de Dados
                </div>
                <div style={{ fontSize: '18px', fontWeight: 700, color: 'hsl(var(--foreground))' }}>
                  1.0
                </div>
              </div>
              <div>
                <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '8px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                  WordPress
                </div>
                <div style={{ fontSize: '18px', fontWeight: 700, color: 'hsl(var(--foreground))' }}>
                  {window.wp?.version || 'N/A'}
                </div>
              </div>
              <div>
                <div style={{ fontSize: '12px', fontWeight: 600, color: 'hsl(var(--muted-foreground))', marginBottom: '8px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                  PHP
                </div>
                <div style={{ fontSize: '18px', fontWeight: 700, color: 'hsl(var(--foreground))' }}>
                  {navigator.userAgent.includes('PHP') ? 'Detectado' : 'N/A'}
                </div>
              </div>
            </div>
            <div style={{ marginTop: '24px', padding: '16px', background: 'hsl(var(--muted))', borderRadius: '8px' }}>
              <p style={{ margin: 0, fontSize: '14px', lineHeight: '1.6', color: 'hsl(var(--muted-foreground))' }}>
                <strong>AcervoX</strong> é um framework profissional e moderno para gestão de acervos digitais no WordPress. 
                Gerencie coleções, itens, metadados personalizados e exiba seu acervo com layouts elegantes. 
                Suporta importação de dados via CSV e integração com outros sistemas de gestão de acervos.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </>
  );
}
