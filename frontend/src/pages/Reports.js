import React, { useState } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import Button from '../components/Button';
import Card from '../components/Card';
import Input from '../components/Input';
import { 
  Users, 
  Building2, 
  FileText, 
  PenTool, 
  BarChart3, 
  DollarSign 
} from 'lucide-react';

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

const FiltersContainer = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
`;

const ReportGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
`;

const ReportCard = styled(Card)`
  padding: 24px;
  cursor: pointer;
  transition: all 0.2s ease;
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  }
`;

const ReportTitle = styled.h3`
  font-size: 18px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0 0 8px 0;
`;

const ReportDescription = styled.p`
  color: ${props => props.theme.colors.textSecondary};
  margin: 0 0 16px 0;
  line-height: 1.5;
`;

const ReportIcon = styled.div`
  width: 48px;
  height: 48px;
  background: ${props => props.color || props.theme.colors.primary};
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  color: white;
  font-size: 24px;
`;

const LoadingContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
  color: ${props => props.theme.colors.textSecondary};
`;

const Reports = () => {
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);
  const [filters, setFilters] = useState({
    startDate: '',
    endDate: '',
    type: ''
  });

  const reportTypes = [
    {
      id: 'users',
      title: 'Relatório de Usuários',
      description: 'Lista completa de usuários cadastrados no sistema com informações detalhadas.',
      icon: Users,
      color: '#374151',
      permission: [1, 2] // Super Admin e Admin Empresa
    },
    {
      id: 'companies',
      title: 'Relatório de Empresas',
      description: 'Informações sobre empresas cadastradas, planos e status de vencimento.',
      icon: Building2,
      color: '#374151',
      permission: [1] // Apenas Super Admin
    },
    {
      id: 'documents',
      title: 'Relatório de Documentos',
      description: 'Estatísticas e detalhes sobre documentos criados e processados.',
      icon: FileText,
      color: '#374151',
      permission: [1, 2, 3] // Todos
    },
    {
      id: 'signatures',
      title: 'Relatório de Assinaturas',
      description: 'Status das assinaturas, pendências e histórico de processamento.',
      icon: PenTool,
      color: '#374151',
      permission: [1, 2, 3] // Todos
    },
    {
      id: 'activities',
      title: 'Relatório de Atividades',
      description: 'Log de atividades e ações realizadas pelos usuários no sistema.',
      icon: BarChart3,
      color: '#374151',
      permission: [1, 2] // Super Admin e Admin Empresa
    },
    {
      id: 'financial',
      title: 'Relatório Financeiro',
      description: 'Informações sobre planos, receitas e análise financeira.',
      icon: DollarSign,
      color: '#374151',
      permission: [1] // Apenas Super Admin
    }
  ];

  const handleGenerateReport = async (reportType) => {
    try {
      setLoading(true);
      
      const params = new URLSearchParams();
      if (filters.startDate) params.append('start_date', filters.startDate);
      if (filters.endDate) params.append('end_date', filters.endDate);
      
      const response = await api.get(`/reports/${reportType}?${params}`, {
        responseType: 'blob'
      });
      
      // Criar link para download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `relatorio_${reportType}_${new Date().toISOString().split('T')[0]}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
    } catch (error) {
      console.error('Erro ao gerar relatório:', error);
      alert('Erro ao gerar relatório. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({ ...prev, [field]: value }));
  };

  const availableReports = reportTypes.filter(report => 
    report.permission.includes(user?.tipo_usuario)
  );

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Relatórios</PageTitle>
      </PageHeader>

      <FiltersContainer>
        <Input
          label="Data Inicial"
          type="date"
          value={filters.startDate}
          onChange={(e) => handleFilterChange('startDate', e.target.value)}
        />
        <Input
          label="Data Final"
          type="date"
          value={filters.endDate}
          onChange={(e) => handleFilterChange('endDate', e.target.value)}
        />
      </FiltersContainer>

      {loading && (
        <LoadingContainer>
          Gerando relatório...
        </LoadingContainer>
      )}

      <ReportGrid>
        {availableReports.map(report => (
          <ReportCard key={report.id} onClick={() => handleGenerateReport(report.id)}>
            <ReportIcon color={report.color}>
              <report.icon size={24} />
            </ReportIcon>
            <ReportTitle>{report.title}</ReportTitle>
            <ReportDescription>{report.description}</ReportDescription>
            <Button $variant="outline" size="small">
              Gerar Relatório
            </Button>
          </ReportCard>
        ))}
      </ReportGrid>
    </PageContainer>
  );
};

export default Reports;