import React, { useState } from 'react';
import { Download, FileText, FileJson, FileCode, X, CheckCircle2 } from 'lucide-react';
import { Button } from './ui/Button';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function ExportData({ collectionId, itemIds = [], onClose }) {
  const [format, setFormat] = useState('csv');
  const [exporting, setExporting] = useState(false);
  const [exported, setExported] = useState(null);
  const { showToast } = useToast();

  const handleExport = async () => {
    if (!collectionId && itemIds.length === 0) {
      showToast('Selecione uma coleção ou itens para exportar', 'warning');
      return;
    }

    setExporting(true);
    setExported(null);

    try {
      const response = await fetch(`/wp-json/acervox/v1/export/${format}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: JSON.stringify({
          collection_id: collectionId || null,
          item_ids: itemIds.length > 0 ? itemIds : []
        })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setExported(data);
        
        // Download automático do arquivo
        const link = document.createElement('a');
        link.href = data.file_url;
        link.download = `acervox-export-${Date.now()}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showToast(
          `${data.total_items} item(ns) exportado(s) com sucesso em formato ${format.toUpperCase()}!`,
          'success'
        );
      } else {
        const errorMessage = data.message || data.error?.message || 'Erro ao exportar dados';
        showToast(errorMessage, 'error');
      }
    } catch (error) {
      console.error('Erro ao exportar', error);
      showToast('Erro ao exportar dados. Tente novamente.', 'error');
    } finally {
      setExporting(false);
    }
  };

  const formatOptions = [
    { value: 'csv', label: 'CSV (Excel)', icon: FileText },
    { value: 'json', label: 'JSON', icon: FileJson },
    { value: 'xml', label: 'XML', icon: FileCode }
  ];

  return (
    <div style={{ padding: '24px' }}>
      <div style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div>
          <h2 style={{ fontSize: '18px', fontWeight: 600, marginBottom: '4px' }}>Exportar Dados</h2>
          <p style={{ fontSize: '14px', color: 'hsl(var(--muted-foreground))' }}>
            Escolha o formato e exporte os dados da coleção
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
            <label className="acervox-form-label">Formato de Exportação</label>
            <Select
              value={format}
              onChange={(e) => setFormat(e.target.value)}
              disabled={exporting}
            >
              {formatOptions.map(option => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </Select>
            <div style={{ marginTop: '8px', fontSize: '12px', color: 'hsl(var(--muted-foreground))' }}>
              {format === 'csv' && 'Ideal para Excel e planilhas'}
              {format === 'json' && 'Formato estruturado para APIs e integrações'}
              {format === 'xml' && 'Formato padrão para intercâmbio de dados'}
            </div>
          </div>

          {exported && (
            <div style={{
              padding: '12px',
              background: 'hsl(var(--muted))',
              borderRadius: '6px',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              fontSize: '14px'
            }}>
              <CheckCircle2 size={16} style={{ color: 'hsl(var(--success))' }} />
              <span>
                {exported.total_items} item(ns) exportado(s) com sucesso!
              </span>
            </div>
          )}

          <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
            {onClose && (
              <Button variant="outline" onClick={onClose} disabled={exporting}>
                Cancelar
              </Button>
            )}
            <Button
              onClick={handleExport}
              disabled={exporting || (!collectionId && itemIds.length === 0)}
            >
              {exporting ? (
                <>
                  <div className="acervox-spinner" style={{ width: '14px', height: '14px', marginRight: '8px' }}></div>
                  Exportando...
                </>
              ) : (
                <>
                  <Download size={16} style={{ marginRight: '8px' }} />
                  Exportar
                </>
              )}
            </Button>
          </div>
      </div>
    </div>
  );
}
