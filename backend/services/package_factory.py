from typing import Dict, Any

class PackageFactory:
    """
    Factory Method Pattern to build package objects.
    Applies specific structures or text modifications based on package type ('tour' vs 'hotel').
    """
    @staticmethod
    def build_package(package_type: str, title: str, location: str, price: float, description: str, image_url: str = None) -> Dict[str, Any]:
        normalized_type = package_type.lower().strip()
        if normalized_type not in ["tour", "hotel"]:
            raise ValueError(f"Invalid package type: {package_type}")

        formatted_title = title.strip()
        # Hotel specific factory formatting logic
        if normalized_type == "hotel":
            if "hotel" not in formatted_title.lower() and "resort" not in formatted_title.lower():
                formatted_title = f"{formatted_title} Hotel"

        # Tour specific factory formatting logic
        elif normalized_type == "tour":
            if "tour" not in formatted_title.lower() and "adventure" not in formatted_title.lower() and "trek" not in formatted_title.lower():
                formatted_title = f"{formatted_title} Tour"

        return {
            "title": formatted_title,
            "location": location.strip(),
            "price": price,
            "description": description.strip(),
            "image_url": image_url.strip() if image_url else None,
            "type": normalized_type
        }
