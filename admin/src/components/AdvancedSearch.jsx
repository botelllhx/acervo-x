import React, { useState } from 'react';
import { Search, Filter, X, Plus, Calendar, Hash } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function AdvancedSearch({ onSearch, collections = [] }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCollection, setSelectedCollection] = useState('');
  const [selectedTags, setSelectedTags] = useState([]);
  const [selectedCategories, setSelectedCategories] = useState([]);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [metaFilters, setMetaFilters] = useState([]);
  const [tags, setTags] = useState([]);
  const [categories, setCategories] = useState([]);
  const { showToast } = useToast();

  React.useEffect(() => {
    // Carregar tags e categorias
    fetch('/wp-json/acervox/v1/tags')
      .then(res => res.json())
      .then(data => setTags(data || []))
      .catch(console.error);

    fetch('/wp-json/acervox/v1/categories')
      .then(res => res.json())
      .then(data => setCategories(data || []))
      .catch(console.error);
  }, []);

  const addMetaFilter = () => {
    setMetaFilters([...metaFilters, { key: '', value: '', compare: 'LIKE' }]);
  };

  const removeMetaFilter = (index) => {
    setMetaFilters(metaFilters.filter((_, i) => i !== index));
  };

  const updateMetaFilter = (index, field, value) => {
    const newFilters = [...metaFilters];
    newFilters[index][field] = value;
    setMetaFilters(newFilters);
  };

  const handleSearch = () => {
    const params = new URLSearchParams();
    
    if (searchTerm) params.append('search', searchTerm);
    if (selectedCollection) params.append('collection', selectedCollection);
    if (selectedTags.length > 0) {
      selectedTags.forEach(tag => params.append('tags[]', tag));
    }
    if (selectedCategories.length > 0) {
      selectedCategories.forEach(cat => params.append('categories[]', cat));
    }
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    if (onSearch) {
      onSearch({
        search: searchTerm,
        collection: selectedCollection,
        tags: selectedTags,
        categories: selectedCategories,
        dateFrom,
        dateTo,
        metaFilters
      });
    }
  };

  const handleReset = () => {
    setSearchTerm('');
    setSelectedCollection('');
    setSelectedTags([]);
    setSelectedCategories([]);
    setDateFrom('');
    setDateTo('');
    setMetaFilters([]);
  };

  return (
    <Card style={{ marginBottom: '24px' }}>
      <CardHeader>
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <Filter size={18} />
          <CardTitle style={{ fontSize: '16px' }}>Busca Avançada</CardTitle>
        </div>
      </CardHeader>
      <CardContent>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          {/* Busca por texto */}
          <div className="acervox-form-group">
            <label className="acervox-form-label">Buscar</label>
            <div style={{ position: 'relative' }}>
              <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'hsl(var(--muted-foreground))' }} />
              <Input
                type="text"
                placeholder="Buscar em título, conteúdo, metadados..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                style={{ paddingLeft: '40px' }}
              />
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '12px' }}>
            {/* Coleção */}
            <div className="acervox-form-group">
              <label className="acervox-form-label">Coleção</label>
              <Select
                value={selectedCollection}
                onChange={(e) => setSelectedCollection(e.target.value)}
              >
                <option value="">Todas as coleções</option>
                {collections.map(col => (
                  <option key={col.id} value={col.id}>{col.title}</option>
                ))}
              </Select>
            </div>

            {/* Tags */}
            <div className="acervox-form-group">
              <label className="acervox-form-label">Tags</label>
              <Select
                multiple
                value={selectedTags}
                onChange={(e) => {
                  const values = Array.from(e.target.selectedOptions, option => option.value);
                  setSelectedTags(values);
                }}
                style={{ minHeight: '38px' }}
              >
                {tags.map(tag => (
                  <option key={tag.id} value={tag.id}>{tag.name} ({tag.count})</option>
                ))}
              </Select>
            </div>

            {/* Categorias */}
            <div className="acervox-form-group">
              <label className="acervox-form-label">Categorias</label>
              <Select
                multiple
                value={selectedCategories}
                onChange={(e) => {
                  const values = Array.from(e.target.selectedOptions, option => option.value);
                  setSelectedCategories(values);
                }}
                style={{ minHeight: '38px' }}
              >
                {categories.map(cat => (
                  <option key={cat.id} value={cat.id}>{cat.name} ({cat.count})</option>
                ))}
              </Select>
            </div>
          </div>

          {/* Range de datas */}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
            <div className="acervox-form-group">
              <label className="acervox-form-label">
                <Calendar size={14} style={{ marginRight: '4px', display: 'inline' }} />
                Data Inicial
              </label>
              <Input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
              />
            </div>
            <div className="acervox-form-group">
              <label className="acervox-form-label">
                <Calendar size={14} style={{ marginRight: '4px', display: 'inline' }} />
                Data Final
              </label>
              <Input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
              />
            </div>
          </div>

          {/* Filtros de metadados */}
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
              <label className="acervox-form-label">
                <Hash size={14} style={{ marginRight: '4px', display: 'inline' }} />
                Filtros de Metadados
              </label>
              <Button variant="ghost" size="sm" onClick={addMetaFilter}>
                <Plus size={14} style={{ marginRight: '4px' }} />
                Adicionar Filtro
              </Button>
            </div>
            {metaFilters.map((filter, index) => (
              <div key={index} style={{ display: 'flex', gap: '8px', marginBottom: '8px', alignItems: 'flex-end' }}>
                <div className="acervox-form-group" style={{ flex: 1 }}>
                  <Input
                    placeholder="Chave do metadado"
                    value={filter.key}
                    onChange={(e) => updateMetaFilter(index, 'key', e.target.value)}
                  />
                </div>
                <div className="acervox-form-group" style={{ flex: 1 }}>
                  <Input
                    placeholder="Valor"
                    value={filter.value}
                    onChange={(e) => updateMetaFilter(index, 'value', e.target.value)}
                  />
                </div>
                <div className="acervox-form-group" style={{ width: '120px' }}>
                  <Select
                    value={filter.compare}
                    onChange={(e) => updateMetaFilter(index, 'compare', e.target.value)}
                  >
                    <option value="LIKE">Contém</option>
                    <option value="=">Igual</option>
                    <option value=">=">Maior ou igual</option>
                    <option value="<=">Menor ou igual</option>
                  </Select>
                </div>
                <Button variant="ghost" size="icon" onClick={() => removeMetaFilter(index)}>
                  <X size={16} />
                </Button>
              </div>
            ))}
          </div>

          {/* Botões de ação */}
          <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
            <Button variant="outline" onClick={handleReset}>
              Limpar
            </Button>
            <Button onClick={handleSearch}>
              <Search size={16} style={{ marginRight: '8px' }} />
              Buscar
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
