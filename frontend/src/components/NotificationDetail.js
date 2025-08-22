import React from 'react';
import styled from 'styled-components';
import { X, Calendar, User, Building } from 'lucide-react';
import { formatDate } from '../utils/dateUtils';

const Overlay = styled.div`
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
`;

const Modal = styled.div`
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-width: 600px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  position: relative;
`;

const Header = styled.div`
  padding: 1.5rem;
  border-bottom: 1px solid ${props => props.theme.colors.border};
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
`;

const CloseButton = styled.button`
  background: none;
  border: none;
  padding: 0.5rem;
  border-radius: 0.375rem;
  color: ${props => props.theme.colors.textSecondary};
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  flex-shrink: 0;

  &:hover {
    background-color: ${props => props.theme.colors.backgroundSecondary};
    color: ${props => props.theme.colors.text};
  }
`;

const Title = styled.h2`
  font-size: 1.25rem;
  font-weight: 600;
  color: ${props => props.theme.colors.text};
  margin: 0;
  line-height: 1.4;
`;

const Content = styled.div`
  padding: 1.5rem;
`;

const Message = styled.div`
  font-size: 1rem;
  line-height: 1.6;
  color: ${props => props.theme.colors.text};
  margin-bottom: 1.5rem;
  white-space: pre-wrap;
`;

const MetaInfo = styled.div`
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background-color: ${props => props.theme.colors.backgroundSecondary};
  border-radius: 0.5rem;
  margin-top: 1rem;
`;

const MetaItem = styled.div`
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.textSecondary};

  svg {
    width: 16px;
    height: 16px;
  }
`;

const TypeBadge = styled.span`
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  
  ${props => {
    switch (props.type) {
      case 'success':
        return `
          background-color: #dcfce7;
          color: #166534;
        `;
      case 'warning':
        return `
          background-color: #fef3c7;
          color: #92400e;
        `;
      case 'error':
        return `
          background-color: #fee2e2;
          color: #991b1b;
        `;
      default:
        return `
          background-color: #dbeafe;
          color: #1e40af;
        `;
    }
  }}
`;

const ReadStatus = styled.div`
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.textSecondary};
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid ${props => props.theme.colors.border};
`;

const ReadIndicator = styled.div`
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: ${props => props.read ? '#10b981' : '#f59e0b'};
`;

function NotificationDetail({ notification, onClose, onMarkAsRead }) {
  // Marcar como lida automaticamente quando abrir
  React.useEffect(() => {
    if (notification && !notification.lida && onMarkAsRead) {
      onMarkAsRead(notification.id);
    }
  }, [notification, onMarkAsRead]);

  if (!notification) return null;

  const handleOverlayClick = (e) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  // Função formatDate agora é importada de dateUtils

  return (
    <Overlay onClick={handleOverlayClick}>
      <Modal>
        <Header>
          <div style={{ flex: 1 }}>
            <Title>{notification.titulo}</Title>
            <div style={{ marginTop: '0.5rem' }}>
              <TypeBadge type={notification.tipo}>
                {notification.tipo}
              </TypeBadge>
            </div>
          </div>
          <CloseButton onClick={onClose}>
            <X size={20} />
          </CloseButton>
        </Header>
        
        <Content>
          <Message>{notification.mensagem}</Message>
          
          <MetaInfo>
            <MetaItem>
              <Calendar />
              <span>Enviado em: {formatDate(notification.data_criacao)}</span>
            </MetaItem>
            
            {notification.remetente_nome && (
              <MetaItem>
                <User />
                <span>De: {notification.remetente_nome} ({notification.remetente_email})</span>
              </MetaItem>
            )}
            
            {notification.empresa_nome && (
              <MetaItem>
                <Building />
                <span>Empresa: {notification.empresa_nome}</span>
              </MetaItem>
            )}
          </MetaInfo>
          
          <ReadStatus>
            <ReadIndicator read={notification.lida} />
            <span>
              {notification.lida 
                ? `Lida em: ${formatDate(notification.data_leitura)}`
                : 'Não lida'
              }
            </span>
          </ReadStatus>
        </Content>
      </Modal>
    </Overlay>
  );
}

export default NotificationDetail;