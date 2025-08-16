import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';

const PageContainer = styled.div`
  padding: 24px;
  max-width: 800px;
  margin: 0 auto;
`;

const PageHeader = styled.div`
  margin-bottom: 32px;
  text-align: center;
`;

const PageTitle = styled.h1`
  font-size: 28px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin-bottom: 8px;
`;

const PageSubtitle = styled.p`
  font-size: 16px;
  color: ${props => props.theme.colors.textSecondary};
  margin: 0;
`;

const ProfileCard = styled(Card)`
  margin-bottom: 24px;
`;

const SectionTitle = styled.h3`
  font-size: 18px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
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

const AvatarSection = styled.div`
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
`;

const Avatar = styled.div`
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: ${props => props.theme.colors.primary};
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 32px;
  font-weight: 600;
`;

const AvatarInfo = styled.div`
  flex: 1;
`;

const UserName = styled.h2`
  font-size: 20px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin-bottom: 4px;
`;

const UserRole = styled.p`
  font-size: 14px;
  color: ${props => props.theme.colors.textSecondary};
  margin: 0;
`;

const InfoGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
`;

const InfoCard = styled.div`
  background-color: ${props => props.theme.colors.gray[50]};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 8px;
  padding: 16px;
  text-align: center;
`;

const InfoLabel = styled.div`
  font-size: 12px;
  font-weight: 500;
  color: ${props => props.theme.colors.textSecondary};
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
`;

const InfoValue = styled.div`
  font-size: 18px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
`;

function Profile() {
  const { user, loadUser } = useAuth();
  const [formData, setFormData] = useState({
    nome: '',
    email: '',
    telefone: '',
    cpf: '',
    endereco: '',
    cidade: '',
    estado: '',
    cep: ''
  });
  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    confirm_password: ''
  });
  const [loading, setLoading] = useState(false);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [stats, setStats] = useState({
    documents_signed: 0,
    documents_pending: 0,
    last_activity: null
  });

  useEffect(() => {
    if (user) {
      setFormData({
        nome: user.nome || '',
        email: user.email || '',
        telefone: user.telefone || '',
        cpf: user.cpf || '',
        endereco: user.endereco || '',
        cidade: user.cidade || '',
        estado: user.estado || '',
        cep: user.cep || ''
      });
    }
    fetchStats();
  }, [user]);

  const fetchStats = async () => {
    try {
      const response = await api.get('/profile/stats');
      setStats(response.data.data);
    } catch (error) {
      console.error('Erro ao carregar estatísticas:', error);
    }
  };

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    setSavingProfile(true);
    setError('');
    setSuccess('');

    try {
      await api.put('/profile', formData);
      setSuccess('Perfil atualizado com sucesso!');
      loadUser(); // Recarrega os dados do usuário
    } catch (error) {
      setError(error.response?.data?.message || 'Erro ao atualizar perfil');
    } finally {
      setSavingProfile(false);
    }
  };

  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    
    if (passwordData.new_password !== passwordData.confirm_password) {
      setError('As senhas não coincidem');
      return;
    }

    setSavingPassword(true);
    setError('');
    setSuccess('');

    try {
      await api.put('/profile/password', {
        current_password: passwordData.current_password,
        new_password: passwordData.new_password
      });
      setSuccess('Senha alterada com sucesso!');
      setPasswordData({
        current_password: '',
        new_password: '',
        confirm_password: ''
      });
    } catch (error) {
      setError(error.response?.data?.message || 'Erro ao alterar senha');
    } finally {
      setSavingPassword(false);
    }
  };

  const getUserTypeLabel = (tipo) => {
    switch (tipo) {
      case 1: return 'Super Administrador';
      case 2: return 'Administrador da Empresa';
      case 3: return 'Assinante';
      default: return 'Usuário';
    }
  };

  const getInitials = (name) => {
    return name
      .split(' ')
      .map(word => word.charAt(0))
      .join('')
      .substring(0, 2)
      .toUpperCase();
  };

  if (!user) {
    return (
      <PageContainer>
        <div>Carregando perfil...</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Meu Perfil</PageTitle>
        <PageSubtitle>Gerencie suas informações pessoais e configurações</PageSubtitle>
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

      <ProfileCard>
        <AvatarSection>
          <Avatar>
            {getInitials(user.nome)}
          </Avatar>
          <AvatarInfo>
            <UserName>{user.nome}</UserName>
            <UserRole>{getUserTypeLabel(user.tipo_usuario)}</UserRole>
            {user.empresa && (
              <UserRole>{user.empresa.nome}</UserRole>
            )}
          </AvatarInfo>
        </AvatarSection>

        <InfoGrid>
          <InfoCard>
            <InfoLabel>Documentos Assinados</InfoLabel>
            <InfoValue>{stats.documents_signed}</InfoValue>
          </InfoCard>
          <InfoCard>
            <InfoLabel>Documentos Pendentes</InfoLabel>
            <InfoValue>{stats.documents_pending}</InfoValue>
          </InfoCard>
          <InfoCard>
            <InfoLabel>Última Atividade</InfoLabel>
            <InfoValue>
              {stats.last_activity 
                ? new Date(stats.last_activity).toLocaleDateString('pt-BR')
                : 'Nunca'
              }
            </InfoValue>
          </InfoCard>
        </InfoGrid>
      </ProfileCard>

      <ProfileCard>
        <SectionTitle>Informações Pessoais</SectionTitle>
        <form onSubmit={handleProfileSubmit}>
          <FormGrid>
            <Input
              label="Nome Completo *"
              value={formData.nome}
              onChange={(e) => setFormData({ ...formData, nome: e.target.value })}
              required
            />
            <Input
              label="E-mail *"
              type="email"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              required
            />
          </FormGrid>
          
          <FormGrid>
            <Input
              label="Telefone"
              value={formData.telefone}
              onChange={(e) => setFormData({ ...formData, telefone: e.target.value })}
              placeholder="(11) 99999-9999"
            />
            <Input
              label="CPF"
              value={formData.cpf}
              onChange={(e) => setFormData({ ...formData, cpf: e.target.value })}
              placeholder="000.000.000-00"
            />
          </FormGrid>
          
          <FormRow>
            <Input
              label="Endereço"
              value={formData.endereco}
              onChange={(e) => setFormData({ ...formData, endereco: e.target.value })}
            />
          </FormRow>
          
          <FormGrid>
            <Input
              label="Cidade"
              value={formData.cidade}
              onChange={(e) => setFormData({ ...formData, cidade: e.target.value })}
            />
            <Input
              label="Estado"
              value={formData.estado}
              onChange={(e) => setFormData({ ...formData, estado: e.target.value })}
              placeholder="SP"
            />
          </FormGrid>
          
          <FormGrid>
            <Input
              label="CEP"
              value={formData.cep}
              onChange={(e) => setFormData({ ...formData, cep: e.target.value })}
              placeholder="00000-000"
            />
          </FormGrid>

          <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '24px' }}>
            <Button type="submit" loading={savingProfile}>
              Salvar Alterações
            </Button>
          </div>
        </form>
      </ProfileCard>

      <ProfileCard>
        <SectionTitle>Alterar Senha</SectionTitle>
        <form onSubmit={handlePasswordSubmit}>
          <FormRow>
            <Input
              label="Senha Atual *"
              type="password"
              value={passwordData.current_password}
              onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
              required
            />
          </FormRow>
          
          <FormGrid>
            <Input
              label="Nova Senha *"
              type="password"
              value={passwordData.new_password}
              onChange={(e) => setPasswordData({ ...passwordData, new_password: e.target.value })}
              required
              minLength={6}
            />
            <Input
              label="Confirmar Nova Senha *"
              type="password"
              value={passwordData.confirm_password}
              onChange={(e) => setPasswordData({ ...passwordData, confirm_password: e.target.value })}
              required
              minLength={6}
            />
          </FormGrid>

          <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '24px' }}>
            <Button type="submit" loading={savingPassword}>
              Alterar Senha
            </Button>
          </div>
        </form>
      </ProfileCard>
    </PageContainer>
  );
}

export default Profile;