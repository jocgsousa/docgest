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

const StatsGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
`;

const StatCard = styled(Card)`
  text-align: center;
  padding: 24px;
`;

const StatValue = styled.div`
  font-size: 32px;
  font-weight: 700;
  color: ${props => props.theme.colors.primary};
  margin-bottom: 8px;
`;

const StatLabel = styled.div`
  font-size: 14px;
  color: ${props => props.theme.colors.textSecondary};
  font-weight: 500;
`;

const StatSubtext = styled.div`
  font-size: 12px;
  color: ${props => props.theme.colors.textSecondary};
  margin-top: 4px;
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

function Usage() {
  const { user } = useAuth();
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
  const [loading, setLoading] = useState(false);
  const [selectedMonth, setSelectedMonth] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    fetchUsage();
    fetchHistory();
  }, [selectedMonth]);

  const fetchUsage = async () => {
    try {
      setLoading(true);
      const params = selectedMonth ? { month: selectedMonth } : {};
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
    const alerts = [];
    const { current_period, limits } = usage;

    // Verificar documentos
    const docPercentage = calculatePercentage(current_period.documents_sent, limits.documents_per_month);
    if (docPercentage > 90) {
      alerts.push({
        type: 'danger',
        title: 'Limite de documentos quase atingido',
        text: `Você já enviou ${current_period.documents_sent} de ${limits.documents_per_month} documentos este mês.`
      });
    } else if (docPercentage > 75) {
      alerts.push({
        type: 'warning',
        title: 'Atenção ao limite de documentos',
        text: `Você já enviou ${current_period.documents_sent} de ${limits.documents_per_month} documentos este mês.`
      });
    }

    // Verificar armazenamento
    const storagePercentage = calculatePercentage(current_period.storage_used_mb, limits.storage_limit_mb);
    if (storagePercentage > 90) {
      alerts.push({
        type: 'danger',
        title: 'Armazenamento quase esgotado',
        text: `Você está usando ${formatBytes(current_period.storage_used_mb * 1024 * 1024)} de ${formatBytes(limits.storage_limit_mb * 1024 * 1024)}.`
      });
    } else if (storagePercentage > 75) {
      alerts.push({
        type: 'warning',
        title: 'Atenção ao armazenamento',
        text: `Você está usando ${formatBytes(current_period.storage_used_mb * 1024 * 1024)} de ${formatBytes(limits.storage_limit_mb * 1024 * 1024)}.`
      });
    }

    return alerts;
  };

  const alerts = getAlerts();

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
          <select
            value={selectedMonth}
            onChange={(e) => setSelectedMonth(e.target.value)}
            style={{
              padding: '8px 12px',
              border: '1px solid #d1d5db',
              borderRadius: '6px',
              fontSize: '14px'
            }}
          >
            <option value="">Mês atual</option>
            {history.map((item) => (
              <option key={item.month} value={item.month}>
                {new Date(item.month + '-01').toLocaleDateString('pt-BR', { 
                  year: 'numeric', 
                  month: 'long' 
                })}
              </option>
            ))}
          </select>
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

      {alerts.map((alert, index) => (
        <AlertCard key={index} type={alert.type}>
          <AlertTitle>{alert.title}</AlertTitle>
          <AlertText>{alert.text}</AlertText>
        </AlertCard>
      ))}

      <StatsGrid>
        <StatCard>
          <StatValue>{usage.current_period.documents_sent}</StatValue>
          <StatLabel>Documentos Enviados</StatLabel>
          <StatSubtext>de {usage.limits.documents_per_month} permitidos</StatSubtext>
        </StatCard>
        
        <StatCard>
          <StatValue>{usage.current_period.documents_signed}</StatValue>
          <StatLabel>Documentos Assinados</StatLabel>
          <StatSubtext>neste período</StatSubtext>
        </StatCard>
        
        <StatCard>
          <StatValue>{formatBytes(usage.current_period.storage_used_mb * 1024 * 1024)}</StatValue>
          <StatLabel>Armazenamento Usado</StatLabel>
          <StatSubtext>de {formatBytes(usage.limits.storage_limit_mb * 1024 * 1024)} disponível</StatSubtext>
        </StatCard>
        
        <StatCard>
          <StatValue>{usage.days_remaining}</StatValue>
          <StatLabel>Dias Restantes</StatLabel>
          <StatSubtext>no período atual</StatSubtext>
        </StatCard>
      </StatsGrid>

      <ChartCard>
        <ChartTitle>Uso Detalhado</ChartTitle>
        
        <UsageItem>
          <div>
            <UsageLabel>Documentos Enviados</UsageLabel>
            <ProgressBar>
              <ProgressFill 
                percentage={calculatePercentage(usage.current_period.documents_sent, usage.limits.documents_per_month)} 
              />
            </ProgressBar>
          </div>
          <UsageValue>
            {usage.current_period.documents_sent} / {usage.limits.documents_per_month}
          </UsageValue>
        </UsageItem>
        
        <UsageItem>
          <div>
            <UsageLabel>Armazenamento</UsageLabel>
            <ProgressBar>
              <ProgressFill 
                percentage={calculatePercentage(usage.current_period.storage_used_mb, usage.limits.storage_limit_mb)} 
              />
            </ProgressBar>
          </div>
          <UsageValue>
            {formatBytes(usage.current_period.storage_used_mb * 1024 * 1024)} / {formatBytes(usage.limits.storage_limit_mb * 1024 * 1024)}
          </UsageValue>
        </UsageItem>
        
        <UsageItem>
          <div>
            <UsageLabel>Chamadas da API</UsageLabel>
            <ProgressBar>
              <ProgressFill 
                percentage={calculatePercentage(usage.current_period.api_calls, usage.limits.api_calls_per_month)} 
              />
            </ProgressBar>
          </div>
          <UsageValue>
            {usage.current_period.api_calls} / {usage.limits.api_calls_per_month}
          </UsageValue>
        </UsageItem>
        
        <UsageItem>
          <div>
            <UsageLabel>Mensagens WhatsApp</UsageLabel>
            <ProgressBar>
              <ProgressFill 
                percentage={calculatePercentage(usage.current_period.whatsapp_messages, usage.limits.whatsapp_messages_per_month)} 
              />
            </ProgressBar>
          </div>
          <UsageValue>
            {usage.current_period.whatsapp_messages} / {usage.limits.whatsapp_messages_per_month}
          </UsageValue>
        </UsageItem>
      </ChartCard>

      {usage.period_start && usage.period_end && (
        <Card>
          <ChartTitle>Período Atual</ChartTitle>
          <p style={{ color: '#6b7280', margin: 0 }}>
            De {new Date(usage.period_start).toLocaleDateString('pt-BR')} até {new Date(usage.period_end).toLocaleDateString('pt-BR')}
          </p>
        </Card>
      )}
    </PageContainer>
  );
}

export default Usage;