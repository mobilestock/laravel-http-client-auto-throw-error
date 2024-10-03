import React, { ImgHTMLAttributes, useEffect, useRef } from 'react'

import tools from '../../../tools'

export const LazyImage: React.FC<ImgHTMLAttributes<HTMLImageElement>> = ({ src, ...props }) => {
  const loaderRef = useRef<HTMLImageElement>(null)
  const observer = useRef<IntersectionObserver>()

  useEffect(() => {
    if (!src) return
    observer.current = new IntersectionObserver(
      (entries: IntersectionObserverEntry[]) => {
        if (entries.some(entry => entry.isIntersecting)) {
          if (loaderRef.current?.src !== src) {
            loaderRef.current?.setAttribute('src', src)
            if (loaderRef.current) {
              observer.current?.unobserve(loaderRef?.current)
            }
          }
        }
      },
      {
        root: null,
        rootMargin: '0px',
        threshold: 0.3
      }
    )
    if (loaderRef.current) {
      observer.current.observe(loaderRef.current)
    }

    return () => {
      observer.current?.disconnect()
    }
  }, [src])

  return <img onError={tools.imageOnError} {...props} ref={loaderRef} alt="Base image" />
}
