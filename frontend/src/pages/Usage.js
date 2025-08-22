import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import Card from '../components/Card';
import Button from '../components/Button';

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

const FiltersContainer = styled.div`
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
`;



const ChartCard = styled(Card)`
  margin-bottom: 24px;
`;

const ChartTitle = styled.h3`
  font-size: 18px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin-bottom: 16px;
`;

const ProgressBar = styled.div`
  width: 100%;
  height: 8px;
  background-color: ${props => props.theme.colors.gray[200]};
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
`;

const ProgressFill = styled.div`
  height: 100%;
  background-color: ${props => {
    if (props.percentage > 90) return props.theme.colors.danger;
    if (props.percentage > 75) return props.theme.colors.warning;
    return props.theme.colors.success;
  }};
  width: ${props => Math.min(props.percentage, 100)}%;
  transition: width 0.3s ease;
`;

const UsageItem = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  
  &:last-child {
    border-bottom: none;
  }
`;

const UsageLabel = styled.div`
  font-weight: 500;
  color: ${props => props.theme.colors.text};
`;

const UsageValue = styled.div`
  font-size: 14px;
  color: ${props => props.theme.colors.textSecondary};
`;

const AlertCard = styled(Card)`
  border-left: 4px solid ${props => {
    if (props.type === 'danger') return props.theme.colors.danger;
    if (props.type === 'warning') return props.theme.colors.warning;
    return props.theme.colors.primary;
  }};
  margin-bottom: 16px;
`;

const AlertTitle = styled.h4`
  font-size: 16px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin-bottom: 8px;
`;

const AlertText = styled.p`
  font-size: 14px;
  color: ${props => props.theme.colors.textSecondary};
  margin: 0;
`;

const LoadingState = styled.div`
  padding: 48px 24px;
  text-align: center;
  color: #6b7280;
`;

const LoadingSpinner = styled.div`
  display: inline-block;
  width: 32px;
  height: 32px;
  border: 3px solid #f3f4f6;
  border-radius: 50%;
  border-top-color: #3b82f6;
  animation: spin 1s ease-in-out infinite;
  margin-bottom: 16px;
  
  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
`;

function Usage() {
  const { user } = useAuth();
  
  // Função para formatar data sem problemas de fuso horário
  const formatDateWithoutTimezone = (dateString) => {
    if (!dateString) return '';
    const [year, month, day] = dateString.split('-');
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('pt-BR');
  };
  
  const [usage, setUsage] = useState({
    current_period: {
      documents_sent: 0,
      documents_signed: 0,
      storage_used_mb: 0,
      api_calls: 0,
      whatsapp_messages: 0
    },
    limits: {
      documents_per_month: 100,
      storage_limit_mb: 1024,
      api_calls_per_month: 1000,
      whatsapp_messages_per_month: 500
    },
    period_start: null,
    period_end: null,
    days_remaining: 0
  });
  const [history, setHistory] = useState([]);
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  // Função para obter o primeiro dia do mês atual
  const getFirstDayOfMonth = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}-01`;
  };

  // Função para obter o último dia do mês atual
  const getLastDayOfMonth = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth() + 1;
    const lastDay = new Date(year, month, 0).getDate();
    const monthStr = String(month).padStart(2, '0');
    const dayStr = String(lastDay).padStart(2, '0');
    return `${year}-${monthStr}-${dayStr}`;
  };

  // Inicializar as datas com o primeiro e último dia do mês atual
  useEffect(() => {
    if (!startDate && !endDate) {
      setStartDate(getFirstDayOfMonth());
      setEndDate(getLastDayOfMonth());
    }
  }, []);
  const [error, setError] = useState('');

  useEffect(() => {
    if (startDate && endDate) {
      fetchUsage();
      fetchHistory();
    }
  }, [startDate, endDate]);

  const fetchUsage = async () => {
    try {
      setLoading(true);
      const params = {};
      if (startDate && endDate) {
        params.start_date = startDate;
        params.end_date = endDate;
      }
      const response = await api.get('/usage/current', { params });
      setUsage(response.data.data);
    } catch (error) {
      console.error('Erro ao carregar uso:', error);
      setError('Erro ao carregar dados de uso');
    } finally {
      setLoading(false);
    }
  };

  const fetchHistory = async () => {
    try {
      const response = await api.get('/usage/history');
      setHistory(response.data.data);
    } catch (error) {
      console.error('Erro ao carregar histórico:', error);
    }
  };

  const calculatePercentage = (used, limit) => {
    return limit > 0 ? (used / limit) * 100 : 0;
  };

  const formatBytes = (bytes) => {
    if (bytes === 0) return '0 MB';
    const mb = bytes / (1024 * 1024);
    return `${mb.toFixed(1)} MB`;
  };

  const getAlerts = () => {
    const alertsArray = [];
    const { current_period, limits } = usage;

    if (!current_period || !limits) return alertsArray;

    // Verificar documentos
    const docPercentage = calculatePercentage(current_period.documents_sent, limits.documents_per_month);
    if (docPercentage > 90) {
      alertsArray.push({
        type: 'danger',
        title: 'Limite de documentos quase atingido',
        text: `Você já enviou ${current_period.documents_sent} de ${limits.documents_per_month} documentos este mês.`
      });
    } else if (docPercentage > 75) {
      alertsArray.push({
        type: 'warning',
        title: 'Atenção ao limite de documentos',
        text: `Você já enviou ${current_period.documents_sent} de ${limits.documents_per_month} documentos este mês.`
      });
    }

    // Verificar armazenamento
    const storagePercentage = calculatePercentage(current_period.storage_used_mb, limits.storage_limit_mb);
    if (storagePercentage > 90) {
      alertsArray.push({
        type: 'danger',
        title: 'Armazenamento quase esgotado',
        text: `Você está usando ${formatBytes(current_period.storage_used_mb * 1024 * 1024)} de ${formatBytes(limits.storage_limit_mb * 1024 * 1024)}.`
      });
    } else if (storagePercentage > 75) {
      alertsArray.push({
        type: 'warning',
        title: 'Atenção ao armazenamento',
        text: `Você está usando ${formatBytes(current_period.storage_used_mb * 1024 * 1024)} de ${formatBytes(limits.storage_limit_mb * 1024 * 1024)}.`
      });
    }

    return alertsArray;
  };

  useEffect(() => {
    if (usage && usage.current_period && usage.limits) {
      setAlerts(getAlerts());
    }
  }, [usage]);

  if (loading && !usage.current_period) {
    return (
      <PageContainer>
        <div>Carregando dados de uso...</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Uso Mensal</PageTitle>
        <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
          <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
            <label style={{ fontSize: '14px', fontWeight: '500' }}>De:</label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              style={{
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            />
          </div>
          <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
            <label style={{ fontSize: '14px', fontWeight: '500' }}>Até:</label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              style={{
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            />
          </div>
          <Button onClick={fetchUsage}>
            Atualizar
          </Button>
        </div>
      </PageHeader>

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

      {alerts && alerts.length > 0 && alerts.map((alert, index) => (
        <AlertCard key={index} type={alert.type}>
          <AlertTitle>{alert.title}</AlertTitle>
          <AlertText>{alert.text}</AlertText>
        </AlertCard>
      ))}

      <ChartCard>
        <ChartTitle>Uso Detalhado</ChartTitle>
        
        {loading ? (
          <LoadingState>
            <LoadingSpinner />
            <p>Carregando dados...</p>
          </LoadingState>
        ) : (
          <>
            <UsageItem>
              <div>
                <UsageLabel>Documentos Enviados</UsageLabel>
                <ProgressBar>
                  <ProgressFill 
                    percentage={calculatePercentage(usage.current_period?.documents_sent || 0, usage.limits?.documents_per_month || 1)} 
                  />
                </ProgressBar>
              </div>
              <UsageValue>
                {usage.current_period?.documents_sent || 0} / {usage.limits?.documents_per_month || 0}
              </UsageValue>
            </UsageItem>
        
            <UsageItem>
              <div>
                <UsageLabel>Armazenamento</UsageLabel>
                <ProgressBar>
                  <ProgressFill 
                    percentage={calculatePercentage(usage.current_period?.storage_used_mb || 0, usage.limits?.storage_limit_mb || 1)} 
                  />
                </ProgressBar>
              </div>
              <UsageValue>
                {formatBytes((usage.current_period?.storage_used_mb || 0) * 1024 * 1024)} / {formatBytes((usage.limits?.storage_limit_mb || 0) * 1024 * 1024)}
              </UsageValue>
            </UsageItem>
            
            <UsageItem>
              <div>
                <UsageLabel>Chamadas da API</UsageLabel>
                <ProgressBar>
                  <ProgressFill 
                    percentage={calculatePercentage(usage.current_period?.api_calls || 0, usage.limits?.api_calls_per_month || 1)} 
                  />
                </ProgressBar>
              </div>
              <UsageValue>
                {usage.current_period?.api_calls || 0} / {usage.limits?.api_calls_per_month || 0}
              </UsageValue>
            </UsageItem>
            
            <UsageItem>
              <div>
                <UsageLabel>Mensagens WhatsApp</UsageLabel>
                <ProgressBar>
                  <ProgressFill 
                    percentage={calculatePercentage(usage.current_period?.whatsapp_messages || 0, usage.limits?.whatsapp_messages_per_month || 1)} 
                  />
                </ProgressBar>
              </div>
              <UsageValue>
                {usage.current_period?.whatsapp_messages || 0} / {usage.limits?.whatsapp_messages_per_month || 0}
              </UsageValue>
            </UsageItem>
          </>
        )}
      </ChartCard>

      {usage.period_start && usage.period_end && (
        <Card>
          <ChartTitle>Período Atual</ChartTitle>
          <p style={{ color: '#6b7280', margin: 0 }}>
            De {formatDateWithoutTimezone(usage.period_start)} até {formatDateWithoutTimezone(usage.period_end)}
          </p>
        </Card>
      )}
    </PageContainer>
  );
}

export default Usage;