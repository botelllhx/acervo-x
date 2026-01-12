import React, { useState, useEffect } from 'react';
import { FileText, Upload, Download, CheckCircle2, XCircle, AlertCircle, Loader2, Archive, FileCheck } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';
import { Select } from './ui/Select';
import { useToast } from './ToastProvider';

export default function ImportCSV() {
  const [collections, setCollections] = useState([]);
  const [selectedCollection, setSelectedCollection] = useState('');
  const [file, setFile] = useState(null);
  const [parsing, setParsing] = useState(false);
  const [importing, setImporting] = useState(false);
  const [parseResult, setParseResult] = useState(null);
  const [result, setResult] = useState(null);
  const [progress, setProgress] = useState({ processed: 0, total: 0, percentage: 0 });
  const [importLog, setImportLog] = useState([]);
  const [errors, setErrors] = useState([]);
  const [completed, setCompleted] = useState(false);
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

  const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    if (selectedFile && selectedFile.type === 'text/csv') {
      setFile(selectedFile);
      setParseResult(null);
      setResult(null);
      setProgress({ processed: 0, total: 0, percentage: 0 });
      setImportLog([]);
      setErrors([]);
      setCompleted(false);
    } else {
      alert('Por favor, selecione um arquivo CSV válido');
    }
  };

  const parseCSV = async () => {
    if (!file) {
      setResult({
        success: false,
        message: 'Por favor, selecione um arquivo CSV',
        isWarning: true
      });
      return;
    }

    if (!selectedCollection) {
      setResult({
        success: false,
        message: 'Por favor, selecione uma coleção antes de importar',
        isWarning: true
      });
      return;
    }

    setParsing(true);
    const formData = new FormData();
    formData.append('file', file);
    formData.append('collection_id', selectedCollection);

    try {
      const response = await fetch('/wp-json/acervox/v1/import/csv/parse', {
        method: 'POST',
        headers: {
          'X-WP-Nonce': AcervoX?.nonce || ''
        },
        body: formData
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setParseResult(data);
        setProgress({ processed: 0, total: data.total_rows, percentage: 0 });
      } else {
        alert(data.message || 'Erro ao processar CSV');
      }
    } catch (error) {
      alert('Erro ao processar CSV: ' + error.message);
    } finally {
      setParsing(false);
    }
  };

  const startImport = async () => {
    if (!parseResult) return;

    setImporting(true);
    setCompleted(false);
    setImportLog([]);
    setErrors([]);

    const batchSize = 10;
    let offset = 0;
    let totalErrors = 0;
    const sessionKey = parseResult.session_key;

    const importBatch = async () => {
      try {
        const response = await fetch('/wp-json/acervox/v1/import/csv', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': AcervoX?.nonce || ''
          },
          body: JSON.stringify({
            session_key: sessionKey,
            offset: offset,
            limit: batchSize
          })
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || data.error || 'Erro na importação');
        }

        // Calcular progresso - usar data.processed que já vem calculado do backend
        const currentProcessed = data.processed !== undefined ? data.processed : (offset + (data.imported || 0));
        const totalRows = parseResult.total_rows;
        const percentage = totalRows > 0 ? Math.min(100, Math.round((currentProcessed / totalRows) * 100)) : 0;

        // Atualizar progresso imediatamente - criar novo objeto para forçar re-render
        const newProgress = {
          processed: currentProcessed,
          total: totalRows,
          percentage: percentage
        };
        
        // Debug (pode remover depois)
        console.log('Progresso atualizado:', newProgress);
        
        setProgress(newProgress);

        if (data.log && data.log.length > 0) {
          setImportLog(prev => [...prev, ...data.log]);
        }

        if (data.errors_list && data.errors_list.length > 0) {
          totalErrors += data.errors_list.length;
          setErrors(prev => [...prev, ...data.errors_list]);
        }

        // Verificar se completou
        const isCompleted = data.completed || currentProcessed >= totalRows;

        if (isCompleted) {
          setCompleted(true);
          setImporting(false);
          // Garantir que o progresso está em 100%
          setProgress(prev => ({
            processed: totalRows,
            total: totalRows,
            percentage: 100
          }));
          
          const totalImported = currentProcessed;
          
          showToast(
            `Importação concluída! ${totalImported} itens processados${totalErrors > 0 ? ` (${totalErrors} erros)` : ''}`,
            totalErrors > 0 ? 'warning' : 'success',
            5000
          );
        } else {
          // Continuar importação
          offset += batchSize;
          // Usar requestAnimationFrame para melhor performance
          requestAnimationFrame(() => {
            setTimeout(importBatch, 300);
          });
        }
      } catch (error) {
        showToast('Erro na importação: ' + error.message, 'error', 5000);
        setImporting(false);
      }
    };

    importBatch();
  };

  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Importar CSV</h1>
      </div>
      <div className="acervox-content">
        <Card style={{ marginBottom: '24px' }}>
          <CardHeader>
            <CardTitle>Importar Itens via CSV</CardTitle>
            <CardDescription>
              Importe itens de um arquivo CSV para uma coleção existente
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
              <div className="acervox-form-group">
                <label className="acervox-form-label">
                  Coleção <span style={{ color: 'hsl(var(--destructive))' }}>*</span>
                </label>
                <Select
                  value={selectedCollection}
                  onChange={(e) => {
                    setSelectedCollection(e.target.value);
                    setResult(null);
                  }}
                  disabled={parsing || importing}
                  style={{
                    borderColor: !selectedCollection && (parsing || importing) ? 'hsl(var(--destructive))' : 'hsl(var(--border))'
                  }}
                >
                  <option value="">-- Selecione uma coleção --</option>
                  {collections.map(col => (
                    <option key={col.id} value={col.id}>
                      {col.title}
                    </option>
                  ))}
                </Select>
                {!selectedCollection && (
                  <p style={{ marginTop: '8px', fontSize: '13px', color: 'hsl(var(--destructive))', display: 'flex', alignItems: 'center', gap: '4px' }}>
                    <AlertCircle size={14} />
                    Selecione uma coleção para continuar
                  </p>
                )}
              </div>

              <div className="acervox-form-group">
                <label className="acervox-form-label">
                  Arquivo CSV <span style={{ color: 'hsl(var(--destructive))' }}>*</span>
                </label>
                <div style={{
                  border: `2px dashed ${file ? 'hsl(var(--primary))' : 'hsl(var(--border))'}`,
                  borderRadius: '8px',
                  padding: '24px',
                  textAlign: 'center',
                  background: file ? 'hsl(var(--muted))' : 'transparent',
                  transition: 'all 0.2s',
                  cursor: parsing || importing ? 'not-allowed' : 'pointer',
                  opacity: parsing || importing ? 0.6 : 1
                }}
                onClick={() => !parsing && !importing && document.getElementById('csv-file-input')?.click()}
                >
                  <input
                    id="csv-file-input"
                    type="file"
                    accept=".csv"
                    onChange={handleFileChange}
                    disabled={parsing || importing}
                    style={{ display: 'none' }}
                  />
                  {file ? (
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '8px' }}>
                      <FileCheck size={32} style={{ color: 'hsl(var(--primary))' }} />
                      <div>
                        <strong style={{ display: 'block', marginBottom: '4px' }}>{file.name}</strong>
                        <span style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))' }}>
                          {(file.size / 1024).toFixed(2)} KB
                        </span>
                      </div>
                    </div>
                  ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '8px' }}>
                      <Upload size={32} style={{ color: 'hsl(var(--muted-foreground))' }} />
                      <div>
                        <strong style={{ display: 'block', marginBottom: '4px' }}>Clique para selecionar ou arraste o arquivo</strong>
                        <span style={{ fontSize: '13px', color: 'hsl(var(--muted-foreground))' }}>
                          Apenas arquivos .csv são aceitos
                        </span>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {result && result.isWarning && (
                <div className="acervox-alert acervox-alert-warning">
                  <AlertCircle size={20} />
                  <div>
                    <strong>{result.message}</strong>
                  </div>
                </div>
              )}

              {!parseResult && (
                <Button
                  onClick={parseCSV}
                  disabled={!file || !selectedCollection || parsing}
                  style={{
                    opacity: (!file || !selectedCollection) ? 0.5 : 1,
                    cursor: (!file || !selectedCollection) ? 'not-allowed' : 'pointer'
                  }}
                >
                  {parsing ? (
                    <>
                      <Loader2 size={16} className="spinning" />
                      Processando CSV...
                    </>
                  ) : (
                    <>
                      <FileText size={16} />
                      Processar CSV
                    </>
                  )}
                </Button>
              )}

              {parseResult && !importing && !completed && (
                <div>
                  <div className="acervox-alert acervox-alert-success" style={{ marginBottom: '16px' }}>
                    <CheckCircle2 size={20} />
                    <div>
                      <strong>CSV processado com sucesso!</strong>
                      <div style={{ marginTop: '12px', display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <Archive size={16} />
                          <span><strong>Coleção:</strong> {collections.find(c => c.id == selectedCollection)?.title || 'N/A'}</span>
                        </div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <FileText size={16} />
                          <span><strong>Total de linhas:</strong> {parseResult.total_rows}</span>
                        </div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <FileCheck size={16} />
                          <span><strong>Colunas:</strong> {parseResult.headers.length} encontradas</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <Button onClick={startImport} style={{ width: '100%' }}>
                    <Upload size={16} />
                    Iniciar Importação
                  </Button>
                </div>
              )}

              {importing && (
                <div>
                  <div style={{ marginBottom: '16px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                      <span style={{ fontSize: '14px', fontWeight: 500 }}>
                        Importando... {progress.processed} de {progress.total}
                      </span>
                      <span style={{ fontSize: '14px', fontWeight: 600 }}>
                        {progress.percentage}%
                      </span>
                    </div>
                    <div style={{
                      width: '100%',
                      height: '24px',
                      background: 'hsl(var(--muted))',
                      borderRadius: '12px',
                      overflow: 'hidden',
                      position: 'relative'
                    }}>
                      <div 
                        key={`progress-${progress.processed}-${progress.percentage}`}
                        style={{
                          width: `${progress.percentage}%`,
                          height: '100%',
                          background: 'hsl(var(--primary))',
                          transition: 'width 0.5s cubic-bezier(0.4, 0, 0.2, 1)',
                          minWidth: progress.percentage > 0 ? '2px' : '0'
                        }} 
                      />
                    </div>
                  </div>
                </div>
              )}

              {completed && (
                <div className="acervox-alert acervox-alert-success">
                  <CheckCircle2 size={20} />
                  <div>
                    <strong>Importação concluída com sucesso!</strong>
                    <div style={{ marginTop: '12px', display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <CheckCircle2 size={16} />
                        <span><strong>{progress.processed}</strong> de <strong>{progress.total}</strong> itens importados</span>
                      </div>
                      {errors.length > 0 && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: 'hsl(var(--destructive))' }}>
                          <XCircle size={16} />
                          <span><strong>{errors.length}</strong> erros encontrados</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              )}

              {importLog.length > 0 && (
                <details style={{ marginTop: '16px' }}>
                  <summary style={{ cursor: 'pointer', fontWeight: 500, marginBottom: '8px' }}>
                    Log de importação ({importLog.length} entradas)
                  </summary>
                  <div style={{
                    maxHeight: '200px',
                    overflowY: 'auto',
                    padding: '12px',
                    background: 'hsl(var(--muted))',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontFamily: 'monospace'
                  }}>
                    {importLog.map((log, idx) => (
                      <div key={idx} style={{ marginBottom: '4px' }}>{log}</div>
                    ))}
                  </div>
                </details>
              )}

              {errors.length > 0 && (
                <details style={{ marginTop: '16px' }}>
                  <summary style={{ cursor: 'pointer', fontWeight: 500, marginBottom: '8px', color: 'hsl(var(--destructive))' }}>
                    Erros ({errors.length})
                  </summary>
                  <div style={{
                    maxHeight: '200px',
                    overflowY: 'auto',
                    padding: '12px',
                    background: '#fef2f2',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontFamily: 'monospace',
                    border: '1px solid hsl(var(--destructive))'
                  }}>
                    {errors.map((error, idx) => (
                      <div key={idx} style={{ marginBottom: '4px', color: 'hsl(var(--destructive))' }}>
                        {error}
                      </div>
                    ))}
                  </div>
                </details>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
      <style>{`
        .spinning {
          animation: spin 1s linear infinite;
        }
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </>
  );
}
