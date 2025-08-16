import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import Card from '../components/Card';
import Button from '../components/Button';
import api from '../services/api';
import { 
  Building2, 
  Users, 
  FileText, 
  PenTool, 
  BarChart3, 
  Clock, 
  User, 
  TrendingUp, 
  Settings, 
  Store, 
  MessageCircle 
} from 'lucide-react';

const DashboardContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
`;

const WelcomeSection = styled.div`
  margin-bottom: 1rem;
`;

const WelcomeTitle = styled.h1`
  font-size: 1.875rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0 0 0.5rem 0;
`;

const WelcomeSubtitle = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  margin: 0;
  font-size: 1rem;
`;

const StatsGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
`;

const StatCard = styled(Card)`
  padding: 1.5rem;
  transition: transform 0.2s ease-in-out;
  
  &:hover {
    transform: translateY(-2px);
  }
`;

const StatHeader = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
`;

const StatIcon = styled.div`
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  background-color: ${props => props.color}15;
  color: ${props => props.color};
`;

const StatValue = styled.div`
  font-size: 2rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.25rem;
`;

const StatLabel = styled.div`
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 0.5rem;
`;

const StatChange = styled.div`
  font-size: 0.75rem;
  font-weight: 500;
  color: ${props => props.positive ? props.theme.colors.success : props.theme.colors.danger};
`;

const ContentGrid = styled.div`
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  
  @media (max-width: 1024px) {
    grid-template-columns: 1fr;
  }
`;

const RecentActivity = styled(Card)`
  padding: 0;
`;

const ActivityHeader = styled.div`
  padding: 1.5rem 1.5rem 0 1.5rem;
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  margin-bottom: 0;
  padding-bottom: 1rem;
`;

const ActivityTitle = styled.h3`
  font-size: 1.125rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0;
`;

const ActivityList = styled.div`
  padding: 1rem 0;
`;

const ActivityItem = styled.div`
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1.5rem;
  transition: background-color 0.2s ease-in-out;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[50]};
  }
`;

const ActivityIcon = styled.div`
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: ${props => props.color}15;
  color: ${props => props.color};
  font-size: 1rem;
  flex-shrink: 0;
`;

const ActivityContent = styled.div`
  flex: 1;
`;

const ActivityText = styled.div`
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.25rem;
`;

const ActivityTime = styled.div`
  font-size: 0.75rem;
  color: ${props => props.theme.colors.gray[500]};
`;

const QuickActions = styled(Card)`
  padding: 1.5rem;
`;

const ActionsTitle = styled.h3`
  font-size: 1.125rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0 0 1rem 0;
`;

const ActionsList = styled.div`
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
`;

function Dashboard() {
  const { user, isAdmin, isCompanyAdmin } = useAuth();
  const [stats, setStats] = useState({
    documentos: 0,
    assinaturas: 0,
    usuarios: 0,
    empresas: 0
  });
  const [activities, setActivities] = useState([]);
  const [loading, setLoading] = useState(false);
  
  useEffect(() => {
    loadDashboardData();
  }, []);
  
  const loadDashboardData = async () => {
    try {
      setLoading(true);
      
      // Carregar estatísticas
      const statsResponse = await api.get('/dashboard/stats');
      setStats(statsResponse.data);
      
      // Carregar atividades recentes
      const activitiesResponse = await api.get('/dashboard/activities');
      setActivities(Array.isArray(activitiesResponse.data) ? activitiesResponse.data : []);
      
    } catch (error) {
      console.error('Erro ao carregar dados do dashboard:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bom dia';
    if (hour < 18) return 'Boa tarde';
    return 'Boa noite';
  };
  
  const getStatsCards = () => {
    if (isAdmin) {
      return [
        {
          icon: Building2,
          color: '#374151',
          value: stats.empresas,
          label: 'Empresas',
          change: '+12%'
        },
        {
          icon: Users,
          color: '#374151',
          value: stats.usuarios,
          label: 'Usuários',
          change: '+8%'
        },
        {
          icon: FileText,
          color: '#374151',
          value: stats.documentos,
          label: 'Documentos',
          change: '+15%'
        },
        {
          icon: PenTool,
          color: '#374151',
          value: stats.assinaturas,
          label: 'Assinaturas',
          change: '+23%'
        }
      ];
    }
    
    if (isCompanyAdmin) {
      return [
        {
          icon: Users,
          color: '#374151',
          value: stats.usuarios,
          label: 'Usuários',
          change: '+5%'
        },
        {
          icon: FileText,
          color: '#374151',
          value: stats.documentos,
          label: 'Documentos',
          change: '+18%'
        },
        {
          icon: PenTool,
          color: '#374151',
          value: stats.assinaturas,
          label: 'Assinaturas',
          change: '+25%'
        },
        {
          icon: BarChart3,
          color: '#374151',
          value: '85%',
          label: 'Uso do Plano',
          change: '+10%'
        }
      ];
    }
    
    // Assinante
    return [
      {
        icon: FileText,
        color: '#374151',
        value: stats.documentos,
        label: 'Meus Documentos',
        change: '+12%'
      },
      {
        icon: PenTool,
        color: '#374151',
        value: stats.assinaturas,
        label: 'Assinaturas',
        change: '+20%'
      },
      {
        icon: Clock,
        color: '#374151',
        value: stats.pendentes || 0,
        label: 'Pendentes',
        change: '-5%'
      }
    ];
  };
  
  const getQuickActions = () => {
    if (isAdmin) {
      return [
        { label: 'Nova Empresa', path: '/empresas/nova', icon: Building2 },
        { label: 'Novo Usuário', path: '/usuarios/novo', icon: User },
        { label: 'Relatórios', path: '/relatorios', icon: TrendingUp },
        { label: 'Configurações', path: '/configuracoes', icon: Settings }
      ];
    }
    
    if (isCompanyAdmin) {
      return [
        { label: 'Novo Documento', path: '/documentos/novo', icon: FileText },
        { label: 'Nova Filial', path: '/filiais/nova', icon: Store },
        { label: 'Novo Usuário', path: '/usuarios/novo', icon: User },
        { label: 'WhatsApp', path: '/whatsapp', icon: MessageCircle }
      ];
    }
    
    return [
      { label: 'Novo Documento', path: '/documentos/novo', icon: FileText },
      { label: 'Minhas Assinaturas', path: '/assinaturas', icon: PenTool },
      { label: 'Meu Perfil', path: '/perfil', icon: User }
    ];
  };
  
  const statsCards = getStatsCards();
  const quickActions = getQuickActions();
  
  return (
    <DashboardContainer>
      <WelcomeSection>
        <WelcomeTitle>
          {getGreeting()}, {user?.nome?.split(' ')[0]}! 👋
        </WelcomeTitle>
        <WelcomeSubtitle>
          Aqui está um resumo das suas atividades recentes.
        </WelcomeSubtitle>
      </WelcomeSection>
      
      <StatsGrid>
        {statsCards.map((stat, index) => (
          <StatCard key={index} hover>
            <StatHeader>
              <StatIcon color={stat.color}>
                <stat.icon size={24} />
              </StatIcon>
            </StatHeader>
            <StatValue>{stat.value}</StatValue>
            <StatLabel>{stat.label}</StatLabel>
            <StatChange positive={!stat.change.startsWith('-')}>
              {stat.change} este mês
            </StatChange>
          </StatCard>
        ))}
      </StatsGrid>
      
      <ContentGrid>
        <RecentActivity>
          <ActivityHeader>
            <ActivityTitle>Atividades Recentes</ActivityTitle>
          </ActivityHeader>
          <ActivityList>
            {activities.length > 0 ? (
              activities.map((activity, index) => (
                <ActivityItem key={index}>
                  <ActivityIcon color={activity.color}>
                    {activity.icon}
                  </ActivityIcon>
                  <ActivityContent>
                    <ActivityText>{activity.text}</ActivityText>
                    <ActivityTime>{activity.time}</ActivityTime>
                  </ActivityContent>
                </ActivityItem>
              ))
            ) : (
              <ActivityItem>
                <ActivityContent>
                  <ActivityText>Nenhuma atividade recente</ActivityText>
                  <ActivityTime>Comece usando o sistema para ver atividades aqui</ActivityTime>
                </ActivityContent>
              </ActivityItem>
            )}
          </ActivityList>
        </RecentActivity>
        
        <QuickActions>
          <ActionsTitle>Ações Rápidas</ActionsTitle>
          <ActionsList>
            {quickActions.map((action, index) => (
              <Button
                key={index}
                $variant="outline"
                size="sm"
                $fullWidth
                onClick={() => window.location.href = action.path}
              >
                <action.icon size={16} style={{ marginRight: '8px' }} />
                {action.label}
              </Button>
            ))}
          </ActionsList>
        </QuickActions>
      </ContentGrid>
    </DashboardContainer>
  );
}

export default Dashboard;