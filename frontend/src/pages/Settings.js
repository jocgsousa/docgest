import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import Button from '../components/Button';
import Card from '../components/Card';
import Input from '../components/Input';


const PageContainer = styled.div`
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
`;

const PageHeader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
`;

const PageTitle = styled.h1`
  font-size: 28px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0;
`;

const SettingsGrid = styled.div`
  display: grid;
  gap: 24px;
`;

const SettingsSection = styled(Card)`
  padding: 24px;
`;

const SectionTitle = styled.h2`
  font-size: 20px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid ${props => props.theme.colors.border};
`;

const FormGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
`;

const FormRow = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
`;

const ToggleContainer = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid ${props => props.theme.colors.border};
  
  &:last-child {
    border-bottom: none;
  }
`;

const ToggleLabel = styled.div`
  flex: 1;
`;

const ToggleTitle = styled.div`
  font-weight: 500;
  color: ${props => props.theme.colors.text.primary};
  margin-bottom: 4px;
`;

const ToggleDescription = styled.div`
  font-size: 14px;
  color: ${props => props.theme.colors.text.secondary};
`;

const Toggle = styled.input.attrs({ type: 'checkbox' })`
  width: 44px;
  height: 24px;
  appearance: none;
  background: ${props => props.checked ? props.theme.colors.primary.main : '#ccc'};
  border-radius: 12px;
  position: relative;
  cursor: pointer;
  transition: background 0.2s ease;
  
  &:before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: ${props => props.checked ? '22px' : '2px'};
    transition: left 0.2s ease;
  }
`;

const ButtonGroup = styled.div`
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
`;

const Settings = () => {
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [settings, setSettings] = useState({
    // Configurações gerais
    app_name: '',
    max_file_size: '10',
    allowed_file_types: 'pdf,doc,docx,jpg,jpeg,png',
    
    // Configurações de email
    smtp_host: '',
    smtp_port: '587',
    smtp_username: '',
    smtp_password: '',
    smtp_from_email: '',
    smtp_from_name: '',
    
    // Configurações de notificação
    email_notifications: true,
    whatsapp_notifications: false,
    signature_reminders: true,
    expiration_alerts: true,
    
    // Configurações de segurança
    password_min_length: '8',
    require_password_complexity: true,
    session_timeout: '24',
    max_login_attempts: '5',
    
    // Configurações de assinatura
    signature_expiration_days: '30',
    auto_reminder_days: '7',
    max_signers_per_document: '10'
  });

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      setLoading(true);
      
      // Primeiro, buscar informações públicas da aplicação (não requer autenticação)
      try {
        const appInfoResponse = await api.get('/app-info');
        if (appInfoResponse.data && appInfoResponse.data.data) {
          setSettings(prev => ({ ...prev, ...appInfoResponse.data.data }));
        }
      } catch (appInfoError) {
        console.warn('Erro ao buscar informações da aplicação:', appInfoError);
      }
      
      // Depois, tentar buscar configurações completas (requer autenticação)
      try {
        const response = await api.get('/settings');
        if (response.data && response.data.data) {
          // Garantir que os dados da API sobrescrevam os valores padrão
          setSettings(prev => {
            const updatedSettings = { ...prev, ...response.data.data };
            console.log('Configurações carregadas da API:', updatedSettings);
            return updatedSettings;
          });
        }
      } catch (settingsError) {
        console.warn('Erro ao buscar configurações completas:', settingsError);
        console.warn('Usando valores padrão para campos não carregados');
        // Se falhar, manter os valores padrão já definidos no estado inicial
      }
      
    } catch (error) {
      console.error('Erro geral ao buscar configurações:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (field, value) => {
    setSettings(prev => ({ ...prev, [field]: value }));
  };

  const handleToggleChange = (field) => {
    setSettings(prev => ({ ...prev, [field]: !prev[field] }));
  };

  const handleSave = async () => {
    try {
      setSaving(true);
      await api.put('/settings', settings);
      alert('Configurações salvas com sucesso!');
    } catch (error) {
      console.error('Erro ao salvar configurações:', error);
      alert('Erro ao salvar configurações. Tente novamente.');
    } finally {
      setSaving(false);
    }
  };

  const handleReset = () => {
    if (window.confirm('Tem certeza que deseja restaurar as configurações padrão?')) {
      fetchSettings();
    }
  };

  const canManageSettings = user?.tipo_usuario === 1; // Apenas Super Admin

  if (!canManageSettings) {
    return (
      <PageContainer>
        <PageHeader>
          <PageTitle>Configurações</PageTitle>
        </PageHeader>
        <Card>
          <div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
            Você não tem permissão para acessar as configurações do sistema.
          </div>
        </Card>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Configurações</PageTitle>
      </PageHeader>

      <SettingsGrid>
        {/* Configurações Gerais */}
        <SettingsSection>
          <SectionTitle>Configurações Gerais</SectionTitle>
          <FormGrid>
            <Input
              label="Nome da Aplicação"
              value={settings.app_name}
              onChange={(e) => handleInputChange('app_name', e.target.value)}
              disabled={loading}
            />
            <Input
              label="Tamanho Máximo de Arquivo (MB)"
              type="number"
              value={settings.max_file_size}
              onChange={(e) => handleInputChange('max_file_size', e.target.value)}
              disabled={loading}
            />
          </FormGrid>
          <Input
            label="Tipos de Arquivo Permitidos (separados por vírgula)"
            value={settings.allowed_file_types}
            onChange={(e) => handleInputChange('allowed_file_types', e.target.value)}
            disabled={loading}
          />
        </SettingsSection>

        {/* Configurações de Email */}
        <SettingsSection>
          <SectionTitle>Configurações de Email</SectionTitle>
          <FormGrid>
            <Input
              label="Servidor SMTP"
              value={settings.smtp_host}
              onChange={(e) => handleInputChange('smtp_host', e.target.value)}
              disabled={loading}
            />
            <Input
              label="Porta SMTP"
              type="number"
              value={settings.smtp_port}
              onChange={(e) => handleInputChange('smtp_port', e.target.value)}
              disabled={loading}
            />
          </FormGrid>
          <FormRow>
            <Input
              label="Usuário SMTP"
              value={settings.smtp_username}
              onChange={(e) => handleInputChange('smtp_username', e.target.value)}
              disabled={loading}
            />
            <Input
              label="Senha SMTP"
              type="password"
              value={settings.smtp_password}
              onChange={(e) => handleInputChange('smtp_password', e.target.value)}
              disabled={loading}
            />
          </FormRow>
          <FormRow>
            <Input
              label="Email Remetente"
              type="email"
              value={settings.smtp_from_email}
              onChange={(e) => handleInputChange('smtp_from_email', e.target.value)}
              disabled={loading}
            />
            <Input
              label="Nome Remetente"
              value={settings.smtp_from_name}
              onChange={(e) => handleInputChange('smtp_from_name', e.target.value)}
              disabled={loading}
            />
          </FormRow>
        </SettingsSection>

        {/* Configurações de Notificação */}
        <SettingsSection>
          <SectionTitle>Configurações de Notificação</SectionTitle>
          <ToggleContainer>
            <ToggleLabel>
              <ToggleTitle>Notificações por Email</ToggleTitle>
              <ToggleDescription>Enviar notificações por email para usuários</ToggleDescription>
            </ToggleLabel>
            <Toggle
              checked={settings.email_notifications}
              onChange={() => handleToggleChange('email_notifications')}
              disabled={loading}
            />
          </ToggleContainer>
          <ToggleContainer>
            <ToggleLabel>
              <ToggleTitle>Notificações por WhatsApp</ToggleTitle>
              <ToggleDescription>Enviar notificações via WhatsApp (requer configuração)</ToggleDescription>
            </ToggleLabel>
            <Toggle
              checked={settings.whatsapp_notifications}
              onChange={() => handleToggleChange('whatsapp_notifications')}
              disabled={loading}
            />
          </ToggleContainer>
          <ToggleContainer>
            <ToggleLabel>
              <ToggleTitle>Lembretes de Assinatura</ToggleTitle>
              <ToggleDescription>Enviar lembretes automáticos para assinaturas pendentes</ToggleDescription>
            </ToggleLabel>
            <Toggle
              checked={settings.signature_reminders}
              onChange={() => handleToggleChange('signature_reminders')}
              disabled={loading}
            />
          </ToggleContainer>
          <ToggleContainer>
            <ToggleLabel>
              <ToggleTitle>Alertas de Expiração</ToggleTitle>
              <ToggleDescription>Alertar sobre documentos próximos ao vencimento</ToggleDescription>
            </ToggleLabel>
            <Toggle
              checked={settings.expiration_alerts}
              onChange={() => handleToggleChange('expiration_alerts')}
              disabled={loading}
            />
          </ToggleContainer>
        </SettingsSection>

        {/* Configurações de Segurança */}
        <SettingsSection>
          <SectionTitle>Configurações de Segurança</SectionTitle>
          <FormGrid>
            <Input
              label="Comprimento Mínimo da Senha"
              type="number"
              min="6"
              max="20"
              value={settings.password_min_length}
              onChange={(e) => handleInputChange('password_min_length', e.target.value)}
              disabled={loading}
            />
            <Input
              label="Timeout de Sessão (horas)"
              type="number"
              min="1"
              max="168"
              value={settings.session_timeout}
              onChange={(e) => handleInputChange('session_timeout', e.target.value)}
              disabled={loading}
            />
          </FormGrid>
          <Input
            label="Máximo de Tentativas de Login"
            type="number"
            min="3"
            max="10"
            value={settings.max_login_attempts}
            onChange={(e) => handleInputChange('max_login_attempts', e.target.value)}
            disabled={loading}
          />
          <ToggleContainer>
            <ToggleLabel>
              <ToggleTitle>Exigir Complexidade de Senha</ToggleTitle>
              <ToggleDescription>Senha deve conter letras, números e símbolos</ToggleDescription>
            </ToggleLabel>
            <Toggle
              checked={settings.require_password_complexity}
              onChange={() => handleToggleChange('require_password_complexity')}
              disabled={loading}
            />
          </ToggleContainer>
        </SettingsSection>

        {/* Configurações de Assinatura */}
        <SettingsSection>
          <SectionTitle>Configurações de Assinatura</SectionTitle>
          <FormGrid>
            <Input
              label="Dias para Expiração de Assinatura"
              type="number"
              min="1"
              max="365"
              value={settings.signature_expiration_days}
              onChange={(e) => handleInputChange('signature_expiration_days', e.target.value)}
              disabled={loading}
            />
            <Input
              label="Dias para Lembrete Automático"
              type="number"
              min="1"
              max="30"
              value={settings.auto_reminder_days}
              onChange={(e) => handleInputChange('auto_reminder_days', e.target.value)}
              disabled={loading}
            />
          </FormGrid>
          <Input
            label="Máximo de Signatários por Documento"
            type="number"
            min="1"
            max="50"
            value={settings.max_signers_per_document}
            onChange={(e) => handleInputChange('max_signers_per_document', e.target.value)}
            disabled={loading}
          />
        </SettingsSection>


      </SettingsGrid>

      {canManageSettings && (
        <ButtonGroup>
        <Button $variant="outline" onClick={handleReset} disabled={loading || saving}>
          Restaurar Padrão
        </Button>
        <Button onClick={handleSave} disabled={loading || saving}>
          {saving ? 'Salvando...' : 'Salvar Configurações'}
        </Button>
      </ButtonGroup>
      )}
    </PageContainer>
  );
};

export default Settings;