import { Dialog, DialogProps, Slide, SlideProps } from '@mui/material'
import { Ref, forwardRef } from 'react'
import { MdClose } from 'react-icons/md'
import styled from 'styled-components'

import { Button } from '@mobilestock/web'

export type GlobalPropsInteracaoSuspensaPadrao = {
  visivel: boolean
  fechar: () => void
}
interface PropsModal
  extends Omit<DialogProps, 'open' | 'onClose' | 'TransitionComponent' | 'fullScreen'>,
    Partial<GlobalPropsInteracaoSuspensaPadrao> {
  children?: React.ReactNode
  titulo?: string | React.ReactNode
  /**
   * @deprecated utilizar visivel
   */
  open?: boolean

  /**
   * @deprecated utilizar fechar
   */
  setOpen?: () => void
}

const Transition = forwardRef(function Transition(props: SlideProps, ref: Ref<unknown>): JSX.Element {
  return <Slide direction="up" ref={ref} {...props} />
})

export const Modal: React.FC<PropsModal> = props => {
  return (
    <ComponenteModal
      {...props}
      fullScreen
      open={!!(props?.visivel || props?.open)}
      onClose={props?.fechar || props?.setOpen}
      TransitionComponent={Transition}
    >
      <div className="cabecalho">
        {!!props.titulo && <>{typeof props.titulo !== 'string' ? <h3>{props.titulo}</h3> : props.titulo}</>}
        {(props?.fechar || props?.setOpen) && (
          <BotaoCabecalho aria-label="fechar" onClick={props?.fechar || props?.setOpen}>
            <MdClose />
          </BotaoCabecalho>
        )}
      </div>
      <div className="anti-cabecalho"></div>
      <div>{props.children}</div>
    </ComponenteModal>
  )
}

const ComponenteModal = styled(Dialog)`
  .cabecalho {
    align-items: center;
    background-color: var(--cor-primaria);
    box-shadow: 0 0.25rem 0.25rem var(--cor-sombra);
    color: var(--branco);
    display: flex;
    height: 4rem;
    justify-content: space-between;
    padding: 0 1rem;
    position: fixed;
    top: 0;
    width: 100vw;
    z-index: 200;

    h1 {
      white-space: nowrap;
      font-family: 'Josefin Sans' !important;
      font-weight: 100;
      margin-left: 1rem;

      @media (max-width: 325px) {
        font-size: 1.4rem;
      }
    }
  }

  .anti-cabecalho {
    padding-top: 4rem;
  }
`

const BotaoCabecalho = styled(Button)`
  border: none;
  box-shadow: none !important;
  font-size: 0;
  background-color: transparent !important;
  color: var(--branco);
  padding: 0.5rem 1rem;
  margin: 0;
  min-width: 0;
  width: fit-content;
  .icon {
    min-height: 1.9rem;
    min-width: 1.9rem;
  }
  @media (max-width: 325px) {
    padding: 0.5rem 0.6rem !important;
    svg {
      font-size: 2rem;
      width: 23px !important;
      height: 23px !important;
    }
  }
  @media (min-width: 326px) {
    svg {
      font-size: 1.8rem;
      width: 25px !important;
      height: 25px !important;
    }
  }
`
