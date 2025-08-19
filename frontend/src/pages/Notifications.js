import React from 'react';
import styled from 'styled-components';
import { useAuth } from '../contexts/AuthContext';
import NotificationManager from '../components/NotificationManager';

const PageContainer = styled.div`
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
`;

const PageHeader = styled.div`
  margin-bottom: 2rem;
`;

const PageTitle = styled.h1`
  font-size: 2rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0 0 0.5rem 0;
`;

const PageDescription = styled.p`
  font-size: 1rem;
  color: ${props => props.theme.colors.gray[600]};
  margin: 0;
`;

const AccessDenied = styled.div`
  text-align: center;
  padding: 3rem;
  background-color: ${props => props.theme.colors.white};
  border-radius: 0.5rem;
  border: 1px solid ${props => props.theme.colors.gray[200]};
`;

const AccessDeniedTitle = styled.h2`
  font-size: 1.5rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0 0 1rem 0;
`;

const AccessDeniedMessage = styled.p`
  font-size: 1rem;
  color: ${props => props.theme.colors.gray[600]};
  margin: 0;
`;

function Notifications() {
  const { user } = useAuth();
  
  // Verificar se o usuário tem permissão (Super Admin ou Admin Empresa)
  const canManageNotifications = user && (user.tipo_usuario === 1 || user.tipo_usuario === 2);
  
  if (!canManageNotifications) {
    return (
      <PageContainer>
        <AccessDenied>
          <AccessDeniedTitle>Acesso Negado</AccessDeniedTitle>
          <AccessDeniedMessage>
            Você não tem permissão para acessar esta página. Apenas Super Administradores e Administradores de Empresa podem gerenciar notificações.
          </AccessDeniedMessage>
        </AccessDenied>
      </PageContainer>
    );
  }
  
  return (
    <PageContainer>
      <PageHeader>
        <PageTitle>Gerenciar Notificações</PageTitle>
        <PageDescription>
          Envie notificações para usuários do sistema. Você pode enviar para todos os usuários, usuários de uma empresa específica ou usuários individuais.
        </PageDescription>
      </PageHeader>
      
      <NotificationManager />
    </PageContainer>
  );
}

export default Notifications;