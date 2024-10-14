import { Stack } from 'expo-router'
import React from 'react'

export default (): React.ReactNode => {
  return (
    <Stack>
      <Stack.Screen name="index" options={{ headerShown: false }} />
    </Stack>
  )
}
