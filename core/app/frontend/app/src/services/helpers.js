export async function sleep(ms) {
  return new Promise((resolve) => {
    setTimeout(resolve, ms)
  })
}

export function is_object(value) {
  return typeof value === 'object'
}
