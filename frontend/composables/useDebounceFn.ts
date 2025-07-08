export const useDebounceFn = (fn: Function, delay: number = 300) => {
  let timeoutId: NodeJS.Timeout

  return (...args: any[]) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn(...args), delay)
  }
}