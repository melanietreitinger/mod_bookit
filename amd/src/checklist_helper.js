
export default class {
    findComponents(components, criteria = {}) {
        const result = [];
        const foundElementIds = new Set();

        for (const component of components) {
            if (!component.element) continue;

            // Überprüfen, ob diese Element-ID bereits gefunden wurde
            const elementId = component.element.id;
            if (foundElementIds.has(elementId)) {
                continue;
            }

            let matches = true;

            if (criteria.elementId && elementId !== criteria.elementId) {
                matches = false;
            }

            if (matches && criteria.selector && !component.element.matches(criteria.selector)) {
                matches = false;
            }

            if (matches && criteria.dataset) {
                for (const [key, value] of Object.entries(criteria.dataset)) {
                    if (component.element.dataset[key] !== value) {
                        matches = false;
                        break;
                    }
                }
            }

            if (matches) {
                foundElementIds.add(elementId);
                result.push(component);

                if (criteria.onlyFirst) {
                    break;
                }
            }
        }

        return result;
    }

}