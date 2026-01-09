import React from 'react';
import { Settings as SettingsIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/Card';
import { Button } from './ui/Button';

export default function Settings() {
  return (
    <>
      <div className="acervox-header">
        <h1 className="acervox-header-title">Configurações</h1>
      </div>
      <div className="acervox-content">
        <Card>
          <CardHeader>
            <CardTitle>Configurações do AcervoX</CardTitle>
            <CardDescription>
              Gerencie as configurações gerais do plugin
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="acervox-empty">
              <div className="acervox-empty-icon">
                <SettingsIcon size={48} />
              </div>
              <div className="acervox-empty-title">Configurações em desenvolvimento</div>
              <div className="acervox-empty-description">
                As configurações avançadas estarão disponíveis em breve
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </>
  );
}
