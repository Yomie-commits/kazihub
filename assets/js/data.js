// Fixed lists used in forms and filters.
export const SERVICE_CATEGORIES = [
    'Graphic Design', 'Web Development', 'Mobile Apps', 'Writing & Translation',
    'Digital Marketing', 'Social Media Management', 'Video & Animation',
    'Virtual Assistance', 'Data Entry & Annotation', 'Photography',
    'Music & Audio', 'Accounting & Finance', 'Tutoring', 'Other',
];

export const JUAKALI_CATEGORIES = [
    'Plumbing', 'Electrical', 'Carpentry', 'Masonry & Construction', 'Welding & Metalwork',
    'Painting', 'Mechanic', 'Cleaning & Laundry', 'House Help', 'Tailoring',
    'Hair & Beauty', 'Moving & Loading', 'Gardening', 'Boda Boda & Delivery',
    'Security', 'Catering', 'Other',
];

export const UAE_CATEGORIES = [
    'Hospitality', 'Construction', 'Driving', 'Security', 'Domestic Work',
    'Retail & Sales', 'Healthcare', 'Cleaning', 'Office & Admin', 'IT', 'Other',
];

export const UAE_EMIRATES = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'];

export const KENYA_COUNTIES = [
    'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Garissa', 'Homa Bay',
    'Isiolo', 'Kajiado', 'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii',
    'Kisumu', 'Kitui', 'Kwale', 'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera',
    'Marsabit', 'Meru', 'Migori', 'Mombasa', "Murang'a", 'Nairobi', 'Nakuru', 'Nandi',
    'Narok', 'Nyamira', 'Nyandarua', 'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River',
    'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu', 'Vihiga', 'Wajir', 'West Pokot',
];

const ICONS = {
    'Graphic Design': 'palette', 'Web Development': 'code-slash', 'Mobile Apps': 'phone',
    'Writing & Translation': 'pencil', 'Digital Marketing': 'graph-up-arrow',
    'Social Media Management': 'instagram', 'Video & Animation': 'camera-reels',
    'Virtual Assistance': 'headset', 'Data Entry & Annotation': 'table', 'Photography': 'camera',
    'Music & Audio': 'music-note-beamed', 'Accounting & Finance': 'calculator', 'Tutoring': 'mortarboard',
    'Plumbing': 'droplet', 'Electrical': 'lightning-charge', 'Carpentry': 'hammer',
    'Masonry & Construction': 'bricks', 'Welding & Metalwork': 'fire', 'Painting': 'paint-bucket',
    'Mechanic': 'wrench', 'Cleaning & Laundry': 'stars', 'House Help': 'house-heart',
    'Tailoring': 'scissors', 'Hair & Beauty': 'brush', 'Moving & Loading': 'truck',
    'Gardening': 'flower1', 'Boda Boda & Delivery': 'bicycle', 'Security': 'shield-check',
    'Catering': 'cup-hot', 'Hospitality': 'building', 'Construction': 'cone-striped',
    'Driving': 'car-front', 'Domestic Work': 'house-heart', 'Retail & Sales': 'shop',
    'Healthcare': 'heart-pulse', 'Cleaning': 'stars', 'Office & Admin': 'briefcase', 'IT': 'pc-display',
};

export function categoryIcon(category) {
    return 'bi-' + (ICONS[category] || 'grid');
}
