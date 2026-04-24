export const GET_MENU_ITEMS = `
  query GetMenuItems($location: MenuLocationEnum = PRIMARY, $language: LanguageCodeFilterEnum = ES) {
    menuItems(where: {location: $location, language: $language}) {
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
