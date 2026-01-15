import React, { useState } from 'react';
import { Edit, X, Save } from 'lucide-react';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function BulkEdit({ itemIds = [], collections = [], onClose, onSuccess }) {
  const [updates, setUpdates] = useState({
    title: '',
    content: '',
    excerpt: '',
    collection_id: '',
    tags: [],
    categories: []
  });
  const [saving, setSaving] = useState(false);
  const { showToast } = useToast();

  const handleSave = async () => {
    if (itemIds.length === 0) {
      showToast('Nenhum item selecionado', 'warning');
      return;
    }

    setSaving(true);

    try {
      const body = {
        item_ids: itemIds,
        updates: {}
      };

      // Adicionar apenas campos preenchidos
      if (updates.title) body.updates.title = updates.title;
      if (updates.content) body.updates.content = updates.content;
      if (updates.excerpt) body.updates.excerpt = updates.excerpt;
      if (updates.collection_id) body.updates.collection_id = parseInt(updates.collection_id);
      if (updates.tags.length > 0) body.updates.tags = updates.tags;
      if (updates.categories.length > 0) body.updates.categories = updates.categories;

      const response = await fetch('/wp-json/acervox/v1/items/bulk-update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify(body)
      });

      const data = await response.json();

      if (response.ok && data.success) {
        showToast(`${data.updated} de ${data.total} item(ns) atualizado(s) com sucesso!`, 'success');
        if (onSuccess) onSuccess();
        if (onClose) onClose();
      } else {
        const errorMessage = data.message || data.error?.message || 'Erro ao atualizar itens';
        showToast(errorMessage, 'error');
      }
    } catch (error) {
      console.error('Erro ao atualizar itens', error);
      showToast('Erro ao atualizar itens. Tente novamente.', 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div style={{ padding: '24px' }}>
      <div style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <h2 style={{ fontSize: '18px', fontWeight: 600, marginBottom: '4px' }}>Edição em Massa</h2>
          <p style={{ fontSize: '14px', color: 'hsl(var(--muted-foreground))' }}>
            Atualizar {itemIds.length} item(ns) selecionado(s)
          </p>
        </div>
        {onClose && (
          <Button variant="ghost" size="sm" onClick={onClose}>
            <X size={16} />
          </Button>
        )}
      </div>
      <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          <div className="acervox-form-group">
            <label className="acervox-form-label">Novo Título (deixe vazio para não alterar)</label>
            <Input
              value={updates.title}
              onChange={(e) => setUpdates({ ...updates, title: e.target.value })}
              placeholder="Deixe vazio para manter o título atual"
            />
          </div>

          <div className="acervox-form-group">
            <label className="acervox-form-label">Nova Descrição (deixe vazio para não alterar)</label>
            <textarea
              className="acervox-input"
              value={updates.content}
              onChange={(e) => setUpdates({ ...updates, content: e.target.value })}
              placeholder="Deixe vazio para manter a descrição atual"
              rows={4}
            />
          </div>

          <div className="acervox-form-group">
            <label className="acervox-form-label">Novo Resumo (deixe vazio para não alterar)</label>
            <Input
              value={updates.excerpt}
              onChange={(e) => setUpdates({ ...updates, excerpt: e.target.value })}
              placeholder="Deixe vazio para manter o resumo atual"
            />
          </div>

          <div className="acervox-form-group">
            <label className="acervox-form-label">Alterar Coleção</label>
            <Select
              value={updates.collection_id}
              onChange={(e) => setUpdates({ ...updates, collection_id: e.target.value })}
            >
              <option value="">Manter coleção atual</option>
              <option value="0">Remover coleção</option>
              {collections.map(col => (
                <option key={col.id} value={col.id}>{col.title}</option>
              ))}
            </Select>
          </div>

          <div style={{
            padding: '12px',
            background: 'hsl(var(--muted))',
            borderRadius: '6px',
            fontSize: '13px',
            color: 'hsl(var(--muted-foreground))'
          }}>
            <strong>Nota:</strong> Apenas os campos preenchidos serão atualizados. Campos vazios manterão os valores atuais dos itens.
          </div>

          <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
            {onClose && (
              <Button variant="outline" onClick={onClose} disabled={saving}>
                Cancelar
              </Button>
            )}
            <Button onClick={handleSave} disabled={saving || itemIds.length === 0}>
              {saving ? (
                <>
                  <div className="acervox-spinner" style={{ width: '14px', height: '14px', marginRight: '8px' }}></div>
                  Salvando...
                </>
              ) : (
                <>
                  <Save size={16} style={{ marginRight: '8px' }} />
                  Atualizar {itemIds.length} Item(ns)
                </>
              )}
            </Button>
          </div>
      </div>
    </div>
  );
}
