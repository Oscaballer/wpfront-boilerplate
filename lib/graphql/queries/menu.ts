export const GET_MENU_ITEMS = `
  query GetMenuItems {
    menuItems(first: 100) {
      nodes {
        id
        parentId
        label
        path
        cssClasses
      }
    }
  }
`;
