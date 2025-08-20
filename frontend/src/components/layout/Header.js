import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useApp } from '../../contexts/AppContext';
import Button from '../common/Button';
import NotificationDetail from '../NotificationDetail';
import api from '../../services/api';
import { 
  Menu, 
  ChevronLeft, 
  ChevronRight, 
  Bell, 
  User, 
  Settings, 
  LogOut,
  X 
} from 'lucide-react';

const HeaderContainer = styled.header`
  background-color: ${props => props.theme.colors.white};
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  padding: 0 1.5rem;
  height: 4rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 30;
`;

const LeftSection = styled.div`
  display: flex;
  align-items: center;
  gap: 1rem;
`;

const MenuButton = styled.button.withConfig({
  shouldForwardProp: (prop) => !['hideOnDesktop', 'hideOnMobile'].includes(prop)
})`
  background: none;
  border: none;
  padding: 0.5rem;
  border-radius: 0.375rem;
  color: ${props => props.theme.colors.gray[600]};
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[100]};
    color: ${props => props.theme.colors.gray[900]};
  }
  
  @media (min-width: 769px) {
    display: ${props => props.hideOnDesktop ? 'none' : 'block'};
  }
  
  @media (max-width: 768px) {
    display: ${props => props.hideOnMobile ? 'none' : 'block'};
  }
`;

const PageTitle = styled.h1`
  font-size: 1.5rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0;
  
  @media (max-width: 640px) {
    font-size: 1.25rem;
  }
`;

const CompanyInfo = styled.div`
  display: flex;
  flex-direction: column;
  margin-left: 1rem;
  
  @media (max-width: 768px) {
    display: none;
  }
`;

const CompanyName = styled.span`
  font-size: 1rem;
  font-weight: 500;
  color: ${props => props.theme.colors.primary[600]};
`;

const CompanyCode = styled.span`
  font-size: 1rem;
  color: ${props => props.theme.colors.gray[600]};
  font-family: monospace;
`;

const RightSection = styled.div`
  display: flex;
  align-items: center;
  gap: 1rem;
`;

const UserMenu = styled.div`
  position: relative;
`;

const UserButton = styled.button`
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  border: none;
  background: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[100]};
  }
`;

const UserAvatar = styled.div`
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background-color: ${props => props.theme.colors.primary};
  display: flex;
  align-items: center;
  justify-content: center;
  color: ${props => props.theme.colors.white};
  font-weight: 600;
  font-size: 0.875rem;
`;

const UserInfo = styled.div`
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  
  @media (max-width: 640px) {
    display: none;
  }
`;

const UserName = styled.span`
  font-weight: 500;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[900]};
`;

const UserRole = styled.span`
  font-size: 0.75rem;
  color: ${props => props.theme.colors.gray[500]};
`;

const DropdownMenu = styled.div`
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background-color: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  min-width: 12rem;
  z-index: 50;
  display: ${props => props.show ? 'block' : 'none'};
`;

const DropdownItem = styled.button`
  width: 100%;
  padding: 0.75rem 1rem;
  text-align: left;
  border: none;
  background: none;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[700]};
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[50]};
    color: ${props => props.theme.colors.gray[900]};
  }
  
  &:first-child {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
  }
  
  &:last-child {
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
  }
`;

const NotificationBadge = styled.div`
  position: relative;
  
  &::after {
    content: '${props => props.count || ''}';
    position: absolute;
    top: -8px;
    right: -8px;
    min-width: 18px;
    height: 18px;
    background-color: ${props => props.theme.colors.danger};
    color: white;
    border-radius: 50%;
    display: ${props => props.show ? 'flex' : 'none'};
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    padding: 2px;
    box-sizing: border-box;
  }
`;

const NotificationModal = styled.div`
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background-color: ${props => props.theme.colors.white};
  border: 1px solid ${props => props.theme.colors.gray[200]};
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  width: 320px;
  max-height: 400px;
  z-index: 50;
  display: ${props => props.show ? 'block' : 'none'};
  overflow: hidden;
`;

const NotificationHeader = styled.div`
  padding: 16px;
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

const NotificationTitle = styled.h3`
  font-size: 16px;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0;
`;

const NotificationList = styled.div`
  max-height: 300px;
  overflow-y: auto;
`;

const NotificationItem = styled.div`
  padding: 12px 16px;
  border-bottom: 1px solid ${props => props.theme.colors.gray[100]};
  cursor: pointer;
  transition: background-color 0.2s;
  background-color: ${props => props.unread ? props.theme.colors.gray[50] : 'transparent'};
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[100]};
  }
  
  &:last-child {
    border-bottom: none;
  }
`;

const NotificationItemTitle = styled.div`
  font-weight: 500;
  font-size: 14px;
  color: ${props => props.theme.colors.text};
  margin-bottom: 4px;
`;

const NotificationItemMessage = styled.div`
  font-size: 13px;
  color: ${props => props.theme.colors.textSecondary};
  margin-bottom: 4px;
  line-height: 1.4;
`;

const NotificationItemTime = styled.div`
  font-size: 12px;
  color: ${props => props.theme.colors.gray[500]};
`;

const NotificationEmpty = styled.div`
  padding: 32px 16px;
  text-align: center;
  color: ${props => props.theme.colors.textSecondary};
  font-size: 14px;
`;

const NotificationFooter = styled.div`
  padding: 12px 16px;
  border-top: 1px solid ${props => props.theme.colors.gray[200]};
  text-align: center;
`;

const NotificationButton = styled.button`
  background: none;
  border: none;
  color: ${props => props.theme.colors.primary};
  font-size: 14px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
  
  &:hover {
    background-color: ${props => props.theme.colors.gray[100]};
  }
`;

function Header({ 
  onToggleSidebar, 
  onToggleMobileSidebar, 
  sidebarCollapsed,
  title
}) {
  const { user, logout } = useAuth();
  const { appInfo } = useApp();
  const navigate = useNavigate();
  
  // Debug log para verificar appInfo no Header
  console.log('🔧 Header - appInfo recebido:', appInfo);
  const [showUserMenu, setShowUserMenu] = useState(false);
  const [showNotifications, setShowNotifications] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const [selectedNotification, setSelectedNotification] = useState(null);
  const [showNotificationDetail, setShowNotificationDetail] = useState(false);
  
  const getUserInitials = (name) => {
    return name
      .split(' ')
      .map(word => word.charAt(0))
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };
  
  const getRoleLabel = (tipoUsuario) => {
    switch (tipoUsuario) {
      case 1:
        return 'Super Admin';
      case 2:
        return 'Admin Empresa';
      case 3:
        return 'Assinante';
      default:
        return 'Usuário';
    }
  };
  
  const handleLogout = () => {
    logout();
    setShowUserMenu(false);
  };

  const handleProfileClick = () => {
    navigate('/perfil');
    setShowUserMenu(false);
  };

  const handleSettingsClick = () => {
    navigate('/configuracoes');
    setShowUserMenu(false);
  };

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const response = await api.get('/notifications');
      // A API retorna: { success: true, data: { notifications: [...], unread_count: 0 } }
      const notificationsData = response.data?.data?.notifications || [];
      setNotifications(Array.isArray(notificationsData) ? notificationsData : []);
    } catch (error) {
      console.error('Erro ao carregar notificações:', error);
      setNotifications([]); // Garantir que sempre seja um array em caso de erro
    } finally {
      setLoading(false);
    }
  };

  const fetchUnreadCount = async () => {
    try {
      const response = await api.get('/notifications/unread-count');
      // A API retorna: { success: true, data: { count: 5 } }
      setUnreadCount(response.data?.data?.count || 0);
    } catch (error) {
      console.error('Erro ao carregar contador de notificações:', error);
    }
  };

  const handleNotificationClick = async (notification) => {
    // Fechar o modal de notificações
    setShowNotifications(false);
    
    // Definir a notificação selecionada e abrir o modal de detalhes
    setSelectedNotification(notification);
    setShowNotificationDetail(true);
    
    // Marcar como lida se ainda não foi lida
    if (!notification.lida) {
      try {
        await api.put(`/notifications/${notification.id}/mark-read`);
        setNotifications(prev => 
          prev.map(n => 
            n.id === notification.id ? { ...n, lida: 1 } : n
          )
        );
        setUnreadCount(prev => Math.max(0, prev - 1));
      } catch (error) {
        console.error('Erro ao marcar notificação como lida:', error);
      }
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await api.post('/notifications/mark-all-read');
      setNotifications(prev => 
        prev.map(n => ({ ...n, lida: 1 }))
      );
      setUnreadCount(0);
    } catch (error) {
      console.error('Erro ao marcar todas como lidas:', error);
    }
  };

  const toggleNotifications = () => {
    setShowNotifications(!showNotifications);
    if (!showNotifications) {
      fetchNotifications();
    }
  };

  const handleCloseNotificationDetail = () => {
    setShowNotificationDetail(false);
    setSelectedNotification(null);
  };

  const formatNotificationTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Agora';
    if (diffInMinutes < 60) return `${diffInMinutes}min atrás`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h atrás`;
    return date.toLocaleDateString('pt-BR');
  };

  useEffect(() => {
    fetchUnreadCount();
    // Atualizar contador a cada 30 segundos
    const interval = setInterval(fetchUnreadCount, 30000);
    return () => clearInterval(interval);
  }, []);

  // Fechar modais ao clicar fora
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (!event.target.closest('.user-menu') && !event.target.closest('.notification-menu')) {
        setShowUserMenu(false);
        setShowNotifications(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);
  
  return (
    <>
    <HeaderContainer>
      <LeftSection>
        <MenuButton 
          onClick={onToggleMobileSidebar}
          hideOnDesktop
        >
          <Menu size={20} />
        </MenuButton>
        
        <MenuButton 
          onClick={onToggleSidebar}
          hideOnMobile
        >
          {sidebarCollapsed ? <ChevronRight size={20} /> : <ChevronLeft size={20} />}
        </MenuButton>
        
        {(user?.tipo_usuario === 2) && user?.empresa_nome && user?.codigo_empresa && (
          <CompanyInfo>
            <CompanyName>{user.empresa_nome}</CompanyName>
            <CompanyCode>Código: {user.codigo_empresa}</CompanyCode>
          </CompanyInfo>
        )}
      </LeftSection>
      
      <RightSection>
        <div className="notification-menu" style={{ position: 'relative' }}>
          <NotificationBadge show={unreadCount > 0} count={unreadCount}>
            <MenuButton onClick={toggleNotifications}>
              <Bell size={20} />
            </MenuButton>
          </NotificationBadge>
          
          <NotificationModal show={showNotifications}>
            <NotificationHeader>
              <NotificationTitle>Notificações</NotificationTitle>
              <MenuButton onClick={() => setShowNotifications(false)}>
                <X size={16} />
              </MenuButton>
            </NotificationHeader>
            
            <NotificationList>
              {loading ? (
                <NotificationEmpty>Carregando...</NotificationEmpty>
              ) : !Array.isArray(notifications) || notifications.length === 0 ? (
                <NotificationEmpty>Nenhuma notificação encontrada</NotificationEmpty>
              ) : (
                notifications.map((notification) => (
                  <NotificationItem
                    key={notification.id}
                    unread={!notification.lida}
                    onClick={() => handleNotificationClick(notification)}
                  >
                    <NotificationItemTitle>{notification.titulo}</NotificationItemTitle>
                    <NotificationItemMessage>{notification.mensagem}</NotificationItemMessage>
                    <NotificationItemTime>
                      {formatNotificationTime(notification.data_criacao)}
                    </NotificationItemTime>
                  </NotificationItem>
                ))
              )}
            </NotificationList>
            
            {Array.isArray(notifications) && notifications.length > 0 && unreadCount > 0 && (
              <NotificationFooter>
                <NotificationButton onClick={handleMarkAllAsRead}>
                  Marcar todas como lidas
                </NotificationButton>
              </NotificationFooter>
            )}
          </NotificationModal>
        </div>
        
        <div className="user-menu">
          <UserMenu>
            <UserButton 
              onClick={() => setShowUserMenu(!showUserMenu)}
            >
              <UserAvatar>
                {getUserInitials(user?.nome || 'U')}
              </UserAvatar>
              <UserInfo>
                <UserName>{user?.nome}</UserName>
                <UserRole>{getRoleLabel(user?.tipo_usuario)}</UserRole>
              </UserInfo>
              <span style={{ fontSize: '0.75rem', color: '#9CA3AF' }}>▼</span>
            </UserButton>
            
            <DropdownMenu show={showUserMenu}>
              <DropdownItem onClick={handleProfileClick}>
                <User size={16} style={{ marginRight: '8px' }} /> Meu Perfil
              </DropdownItem>
              {user?.tipo_usuario === 1 && (
                <DropdownItem onClick={handleSettingsClick}>
                  <Settings size={16} style={{ marginRight: '8px' }} /> Configurações
                </DropdownItem>
              )}
              <DropdownItem onClick={handleLogout}>
                <LogOut size={16} style={{ marginRight: '8px' }} /> Sair
              </DropdownItem>
            </DropdownMenu>
          </UserMenu>
        </div>
      </RightSection>
    </HeaderContainer>
    
    {/* Modal de detalhes da notificação */}
    {showNotificationDetail && selectedNotification && (
      <NotificationDetail
        notification={selectedNotification}
        onClose={handleCloseNotificationDetail}
        onMarkAsRead={async (notificationId) => {
          try {
            await api.put(`/notifications/${notificationId}/mark-read`);
            setNotifications(prev => 
              prev.map(n => 
                n.id === notificationId ? { ...n, lida: 1 } : n
              )
            );
            setUnreadCount(prev => Math.max(0, prev - 1));
          } catch (error) {
            console.error('Erro ao marcar notificação como lida:', error);
          }
        }}
      />
    )}
    </>
  );
}

export default Header;