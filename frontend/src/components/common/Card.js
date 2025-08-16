import styled, { css } from 'styled-components';

const Card = styled.div`
  background-color: ${props => props.theme.colors.white};
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid ${props => props.theme.colors.gray[200]};
  overflow: hidden;
  transition: all 0.2s ease-in-out;
  
  ${props => props.hover && css`
    &:hover {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transform: translateY(-1px);
    }
  `}
  
  ${props => props.padding && css`
    padding: ${props.padding};
  `}
  
  ${props => props.noPadding && css`
    padding: 0;
  `}
  
  ${props => !props.noPadding && !props.padding && css`
    padding: 1.5rem;
  `}
`;

const CardHeader = styled.div`
  padding: 1.5rem 1.5rem 0 1.5rem;
  border-bottom: 1px solid ${props => props.theme.colors.gray[200]};
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  
  ${props => props.noBorder && css`
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  `}
`;

const CardTitle = styled.h3`
  font-size: 1.125rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[900]};
  margin: 0;
`;

const CardSubtitle = styled.p`
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[600]};
  margin: 0.25rem 0 0 0;
`;

const CardBody = styled.div`
  padding: 0 1.5rem;
  
  ${props => props.noPadding && css`
    padding: 0;
  `}
`;

const CardFooter = styled.div`
  padding: 1rem 1.5rem 1.5rem 1.5rem;
  border-top: 1px solid ${props => props.theme.colors.gray[200]};
  margin-top: 1.5rem;
  
  ${props => props.noBorder && css`
    border-top: none;
    margin-top: 0;
  `}
  
  ${props => props.noPadding && css`
    padding: 0;
  `}
`;

Card.Header = CardHeader;
Card.Title = CardTitle;
Card.Subtitle = CardSubtitle;
Card.Body = CardBody;
Card.Footer = CardFooter;

export default Card;