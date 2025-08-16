import React from 'react';
import styled from 'styled-components';

const StyledCard = styled.div`
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 24px;
  margin-bottom: 24px;
  border: 1px solid #e2e8f0;
  transition: box-shadow 0.2s ease;

  &:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  }
`;

const CardHeader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: ${props => props.hasContent ? '16px' : '0'};
`;

const CardTitle = styled.h3`
  font-size: 18px;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
`;

const CardContent = styled.div`
  color: #4a5568;
`;

const Card = ({ title, children, headerActions, className, ...props }) => {
  return (
    <StyledCard className={className} {...props}>
      {(title || headerActions) && (
        <CardHeader hasContent={!!children}>
          {title && <CardTitle>{title}</CardTitle>}
          {headerActions && <div>{headerActions}</div>}
        </CardHeader>
      )}
      {children && <CardContent>{children}</CardContent>}
    </StyledCard>
  );
};

export default Card;