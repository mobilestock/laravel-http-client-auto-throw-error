import path from 'path'
import React, { useState } from 'react'
import { FaSearch } from 'react-icons/fa'
import styled from 'styled-components'

import { navigate } from '@storybook/addon-links'

import { searchForGlobalAnchors, searchMdx } from './search'

interface SearchResult {
  filePath: string
  content: string
  url: string
  globals: string[]
  title: string
  matchIndex: number
}

const SearchComponent: React.FC = () => {
  const [query, setQuery] = useState('')
  const [showSearch, setShowSearch] = useState(false)
  const [results, setResults] = useState<SearchResult[]>([])

  const handleSearch = (event: React.ChangeEvent<HTMLInputElement>) => {
    const value = event.target.value
    setQuery(value)

    const searchResults = searchMdx(value).map((result: any) => {
      const matchIndex = result.content.toLowerCase().indexOf(value.toLowerCase())
      return {
        filePath: result.filePath,
        matchIndex,
        content: result.content,
        url: result.url,
        globals: result.globals,
        title: result.title
      }
    })
    setResults(searchResults)
  }

  const getAutocompleteText = (content: string, query: string) => {
    if (!query) return ''
    const index = content.toLowerCase().indexOf(query.toLowerCase())
    if (index === -1) return ''
    const autocompleteText = content.slice(index + query.length, index + query.length + 40)

    return autocompleteText
  }

  const redirect = (title: string, globals: string[], query: string) => {
    const possibleHash = searchForGlobalAnchors(query, globals)

    navigate({ title: title })
    if (!!possibleHash) {
      setTimeout(() => {
        const element = document.getElementById(possibleHash)
        if (element) {
          element.scrollIntoView({ behavior: 'smooth' })
        }
      }, 450)
    }

    setShowSearch(false)
  }

  return (
    <>
      <SearchContainer>
        <SearchIcon onClick={() => setShowSearch(!showSearch)}>
          <FaSearch />
        </SearchIcon>
        {showSearch && (
          <Input type="text" placeholder="Pesquisar..." value={query} onChange={handleSearch} />
        )}
      </SearchContainer>
      {showSearch && (
        <Container>
          {!!results && (
            <List>
              {results.map((result, index) => {
                let autocompleteText = getAutocompleteText(result.content, query)
                let fileName = path.basename(result.filePath).replace('.mdx', '')
              return (
                <ListItem key={index}>
                  <strong>{fileName + ': '}</strong>
                  <button
                    id={result.matchIndex.toString()}
                    onClick={() => redirect(result.title, result.globals, query)}
                  >
                    <span>{query}</span>
                    <span style={{ opacity: 0.5 }}>{autocompleteText}...</span>
                  </button>
                </ListItem>
              )
            })}
          </List>
          )}
        </Container>
      )}
    </>
  )
}

const SearchContainer = styled.div`
  position: fixed;
  top: 10px;
  left: 10px;
  display: flex;
  align-items: center;
  z-index: 1000;

  :hover {
    background-color: ${({ theme }) => theme.colors.container.outline.default};
  }
`

const SearchIcon = styled.div`
  cursor: pointer;
  background-color: ${({ theme }) => theme.colors.container.pure};
  padding: 10px;
  border-radius: 50%;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
`

const Container = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  background-color: ${({ theme }) => theme.colors.container.default};
  padding: 20px;
  margin: 5px 0;
  position: fixed;
  top: 50px;
  left: 10px;
  z-index: 1000;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  border-radius: 5px;
`

const Input = styled.input`
  color: ${({ theme }) => theme.colors.text.default};
  background-color: ${({ theme }) => theme.colors.container.default};
  border: 1px solid ${({ theme }) => theme.colors.container.outline.default};
  border-radius: 5px;
  padding: 5px;
  margin-left: 10px;
  width: 300px;
`

const List = styled.ul`
  padding: 2px;
  margin: 2px 0;
`

const ListItem = styled.li`
  background-color: ${({ theme }) => theme.colors.container.pure};
  padding: 2px;
  margin: 2px;
  border: 1px solid ${({ theme }) => theme.colors.container.outline.pure};
  border-radius: 5px;
`

export default SearchComponent
