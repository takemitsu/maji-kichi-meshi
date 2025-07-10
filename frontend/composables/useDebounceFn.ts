export const useDebounceFn = <T extends unknown[]>(fn: (...args: T) => void, delay: number = 300) => {
  let timeoutId: NodeJS.Timeout

  return (...args: T) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn(...args), delay)
  }
}
