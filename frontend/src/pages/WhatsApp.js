import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import api from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import Modal from '../components/Modal';

const PageContainer = styled.div`
  padding: 24px;
`;

const PageHeader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
`;

const PageTitle = styled.h1`
  font-size: 24px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0;
`;

const ConfigCard = styled(Card)`
  margin-bottom: 24px;
`;

const ConfigSection = styled.div`
  margin-bottom: 32px;
  
  &:last-child {
    margin-bottom: 0;
  }
`;

const SectionTitle = styled.h3`
  font-size: 18px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin-bottom: 16px;
`;

const FormGrid = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
  
  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
`;

const FormRow = styled.div`
  grid-column: 1 / -1;
`;

const StatusIndicator = styled.div`
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 16px;
  
  ${props => props.connected ? `
    background-color: #dcfce7;
    color: #166534;
  ` : `
    background-color: #fee2e2;
    color: #991b1b;
  `}
`;

const StatusDot = styled.div`
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: currentColor;
`;

const MessagePreview = styled.div`
  background-color: ${props => props.theme.colors.gray[50]};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 6px;
  padding: 16px;
  margin-top: 16px;
`;

const PreviewTitle = styled.h4`
  font-size: 14px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin-bottom: 8px;
`;

const PreviewContent = styled.div`
  font-size: 14px;
  color: ${props => props.theme.colors.textSecondary};
  white-space: pre-wrap;
`;

function WhatsApp() {
  const { user } = useAuth();
  const { showSuccess, showError } = useToast();
  const [config, setConfig] = useState({
    webhook_url: '',
    api_token: '',
    phone_number: '',
    welcome_message: '',
    signature_reminder_message: '',
    document_ready_message: '',
    auto_send_reminders: false,
    reminder_interval_hours: 24
  });
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [connected, setConnected] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [showTestModal, setShowTestModal] = useState(false);
  const [testMessage, setTestMessage] = useState('');
  const [testPhone, setTestPhone] = useState('');

  useEffect(() => {
    fetchConfig();
    checkConnection();
  }, []);

  const fetchConfig = async () => {
    try {
      setLoading(true);
      const response = await api.get('/whatsapp/config');
      setConfig(response.data.data);
    } catch (error) {
      console.error('Erro ao carregar configuração:', error);
      setError('Erro ao carregar configuração do WhatsApp');
    } finally {
      setLoading(false);
    }
  };

  const checkConnection = async () => {
    try {
      const response = await api.get('/whatsapp/status');
      setConnected(response.data.data.connected);
    } catch (error) {
      console.error('Erro ao verificar conexão:', error);
      setConnected(false);
    }
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    setSuccess('');

    try {
      await api.put('/whatsapp/config', config);
      setSuccess('Configuração salva com sucesso!');
      showSuccess('Configuração salva com sucesso!');
      checkConnection();
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Erro ao salvar configuração';
      setError(errorMessage);
      showError(errorMessage);
    } finally {
      setSaving(false);
    }
  };

  const handleTest = async () => {
    if (!testPhone || !testMessage) {
      showError('Preencha o telefone e a mensagem para teste');
      return;
    }

    setTesting(true);
    setError('');
    setSuccess('');

    try {
      await api.post('/whatsapp/test', {
        phone: testPhone,
        message: testMessage
      });
      setSuccess('Mensagem de teste enviada com sucesso!');
      showSuccess('Mensagem de teste enviada com sucesso!');
      setShowTestModal(false);
      setTestPhone('');
      setTestMessage('');
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Erro ao enviar mensagem de teste';
      setError(errorMessage);
      showError(errorMessage);
    } finally {
      setTesting(false);
    }
  };

  const handleInputChange = (field, value) => {
    setConfig(prev => ({ ...prev, [field]: value }));
  };

  if (loading) {
    return (
      <PageContainer>
        <div>Carregando configurações...</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Configuração WhatsApp</PageTitle>
        <div style={{ display: 'flex', gap: '12px' }}>
          <Button
            variant="outline"
            onClick={() => setShowTestModal(true)}
            disabled={!connected}
          >
            Testar Envio
          </Button>
          <Button onClick={checkConnection}>
            Verificar Conexão
          </Button>
        </div>
      </PageHeader>

      <StatusIndicator connected={connected}>
        <StatusDot />
        {connected ? 'WhatsApp conectado e funcionando' : 'WhatsApp desconectado ou com problemas'}
      </StatusIndicator>

      {error && (
        <div style={{ 
          padding: '12px', 
          backgroundColor: '#fee2e2', 
          color: '#991b1b', 
          borderRadius: '6px', 
          marginBottom: '16px' 
        }}>
          {error}
        </div>
      )}

      {success && (
        <div style={{ 
          padding: '12px', 
          backgroundColor: '#dcfce7', 
          color: '#166534', 
          borderRadius: '6px', 
          marginBottom: '16px' 
        }}>
          {success}
        </div>
      )}

      <form onSubmit={handleSave}>
        <ConfigCard>
          <ConfigSection>
            <SectionTitle>Configurações de Conexão</SectionTitle>
            <FormGrid>
              <Input
                label="URL do Webhook"
                value={config.webhook_url}
                onChange={(e) => handleInputChange('webhook_url', e.target.value)}
                placeholder="https://api.whatsapp.com/webhook"
              />
              <Input
                label="Token da API"
                type="password"
                value={config.api_token}
                onChange={(e) => handleInputChange('api_token', e.target.value)}
                placeholder="Seu token de acesso"
              />
            </FormGrid>
            <FormRow>
              <Input
                label="Número do WhatsApp"
                value={config.phone_number}
                onChange={(e) => handleInputChange('phone_number', e.target.value)}
                placeholder="5511999999999"
              />
            </FormRow>
          </ConfigSection>

          <ConfigSection>
            <SectionTitle>Mensagens Automáticas</SectionTitle>
            <FormRow>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
                Mensagem de Boas-vindas
              </label>
              <textarea
                value={config.welcome_message}
                onChange={(e) => handleInputChange('welcome_message', e.target.value)}
                placeholder="Olá! Bem-vindo ao nosso sistema de documentos..."
                rows={3}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  resize: 'vertical'
                }}
              />
            </FormRow>
            
            <FormRow>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
                Lembrete de Assinatura
              </label>
              <textarea
                value={config.signature_reminder_message}
                onChange={(e) => handleInputChange('signature_reminder_message', e.target.value)}
                placeholder="Você tem documentos pendentes de assinatura..."
                rows={3}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  resize: 'vertical'
                }}
              />
            </FormRow>
            
            <FormRow>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
                Documento Pronto
              </label>
              <textarea
                value={config.document_ready_message}
                onChange={(e) => handleInputChange('document_ready_message', e.target.value)}
                placeholder="Seu documento foi processado e está pronto..."
                rows={3}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid #d1d5db',
                  borderRadius: '6px',
                  fontSize: '14px',
                  resize: 'vertical'
                }}
              />
            </FormRow>
          </ConfigSection>

          <ConfigSection>
            <SectionTitle>Configurações de Lembrete</SectionTitle>
            <FormRow>
              <label style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '16px' }}>
                <input
                  type="checkbox"
                  checked={config.auto_send_reminders}
                  onChange={(e) => handleInputChange('auto_send_reminders', e.target.checked)}
                />
                Enviar lembretes automáticos
              </label>
            </FormRow>
            
            {config.auto_send_reminders && (
              <FormRow>
                <Input
                  label="Intervalo entre lembretes (horas)"
                  type="number"
                  value={config.reminder_interval_hours}
                  onChange={(e) => handleInputChange('reminder_interval_hours', parseInt(e.target.value))}
                  min="1"
                  max="168"
                />
              </FormRow>
            )}
          </ConfigSection>
        </ConfigCard>

        <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
          <Button type="submit" loading={saving}>
            Salvar Configurações
          </Button>
        </div>
      </form>

      <Modal
        isOpen={showTestModal}
        onClose={() => {
          setShowTestModal(false);
          setTestPhone('');
          setTestMessage('');
          setError('');
        }}
        title="Testar Envio de Mensagem"
      >
        <div>
          <Input
            label="Número do WhatsApp"
            value={testPhone}
            onChange={(e) => setTestPhone(e.target.value)}
            placeholder="5511999999999"
            style={{ marginBottom: '16px' }}
          />
          
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
            Mensagem
          </label>
          <textarea
            value={testMessage}
            onChange={(e) => setTestMessage(e.target.value)}
            placeholder="Digite sua mensagem de teste..."
            rows={4}
            style={{
              width: '100%',
              padding: '8px 12px',
              border: '1px solid #d1d5db',
              borderRadius: '6px',
              fontSize: '14px',
              resize: 'vertical',
              marginBottom: '16px'
            }}
          />

          {error && (
            <div style={{ 
              padding: '12px', 
              backgroundColor: '#fee2e2', 
              color: '#991b1b', 
              borderRadius: '6px', 
              marginBottom: '16px' 
            }}>
              {error}
            </div>
          )}

          <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
            <Button
              type="button"
              variant="outline"
              onClick={() => setShowTestModal(false)}
            >
              Cancelar
            </Button>
            <Button onClick={handleTest} loading={testing}>
              Enviar Teste
            </Button>
          </div>
        </div>
      </Modal>
    </PageContainer>
  );
}

export default WhatsApp;