// =====================================================
// FACIAL ANALYSIS RECOMMENDATIONS DATABASE
// =====================================================

const RECOMMENDATIONS = {
    // ==================== HAIRSTYLE RECOMMENDATIONS ====================
    hairstyles: {
        male: {
            oval: {
                recommended: [
                    "Textured crop",
                    "Side part",
                    "Short quiff",
                    "Slicked back",
                    "Classic pompadour"
                ],
                avoid: [
                    "Heavy fringe covering forehead",
                    "Very long hair without layers"
                ]
            },
            round: {
                recommended: [
                    "High fade with volume on top",
                    "Angular fringe",
                    "Vertical quiff",
                    "Side-swept undercut",
                    "Pompadour with height"
                ],
                avoid: [
                    "Bowl cuts",
                    "Rounded styles",
                    "Flat, close-cropped cuts",
                    "Full, wide styles"
                ]
            },
            square: {
                recommended: [
                    "Textured fringe",
                    "Messy quiff",
                    "Side part with volume",
                    "Soft layered cut",
                    "Wavy top with tapered sides"
                ],
                avoid: [
                    "Buzz cuts",
                    "Slicked back straight hair",
                    "Angular geometric cuts",
                    "Very short military cuts"
                ]
            },
            oblong: {
                recommended: [
                    "Medium length with side volume",
                    "Textured fringe",
                    "Side-swept bangs",
                    "Layered cut with width",
                    "Short sides with fuller top"
                ],
                avoid: [
                    "Very short buzz cuts",
                    "Long straight hair",
                    "High vertical quiff",
                    "Styles that add height"
                ]
            },
            heart: {
                recommended: [
                    "Side part",
                    "Textured fringe covering forehead",
                    "Medium length swept to side",
                    "Soft layered cut",
                    "Chin-length with layers"
                ],
                avoid: [
                    "Very short sides",
                    "Slicked back exposing forehead",
                    "High volume on top",
                    "Tight styles"
                ]
            }
        },
        female: {
            oval: {
                recommended: [
                    "Long layers",
                    "Bob cut",
                    "Pixie cut",
                    "Beach waves",
                    "Almost any style works well"
                ],
                avoid: [
                    "Styles that hide facial features",
                    "Overly heavy bangs"
                ]
            },
            round: {
                recommended: [
                    "Long layers past shoulders",
                    "Side-swept bangs",
                    "Asymmetrical bob",
                    "High ponytail",
                    "Vertical curls"
                ],
                avoid: [
                    "Blunt bangs",
                    "Chin-length bobs",
                    "Rounded cuts",
                    "Very short styles",
                    "Center parts"
                ]
            },
            square: {
                recommended: [
                    "Long layers with waves",
                    "Side-swept bangs",
                    "Soft curls",
                    "Shoulder-length with layers",
                    "Wispy fringe"
                ],
                avoid: [
                    "Blunt cuts at jaw length",
                    "Straight, severe styles",
                    "Center parts",
                    "Very short bobs"
                ]
            },
            oblong: {
                recommended: [
                    "Shoulder-length with layers",
                    "Curtain bangs",
                    "Soft waves adding width",
                    "Bob with volume at sides",
                    "Layered fringe"
                ],
                avoid: [
                    "Very long straight hair",
                    "High updos",
                    "Styles adding height",
                    "Center parts without bangs"
                ]
            },
            heart: {
                recommended: [
                    "Chin-length bob",
                    "Side-swept bangs",
                    "Layers starting at chin",
                    "Soft waves at jaw level",
                    "Wispy fringe"
                ],
                avoid: [
                    "Short crops",
                    "Volume at crown",
                    "Styles pulled back tightly",
                    "Very short bangs"
                ]
            }
        }
    },

    // ==================== EYEBROW RECOMMENDATIONS ====================
    eyebrows: {
        male: {
            oval: {
                current: "Natural, well-defined brows with moderate thickness",
                suggestion: "Maintain natural shape with light grooming. Remove stray hairs and keep the arch subtle for a masculine look."
            },
            round: {
                current: "Naturally fuller brows with soft arch",
                suggestion: "Create a more defined arch to add angles. Keep brows groomed but full to add structure to the face."
            },
            square: {
                current: "Strong, straight brows with natural thickness",
                suggestion: "Soften the brow line slightly with a gentle arch. Avoid overly angular shapes that emphasize squareness."
            },
            oblong: {
                current: "Straight or slightly arched natural brows",
                suggestion: "Keep brows fuller and straighter to add width. Avoid high arches that elongate the face further."
            },
            heart: {
                current: "Naturally shaped brows with gentle arch",
                suggestion: "Maintain a soft, rounded arch. Keep brows groomed but not too thin to balance the wider forehead."
            }
        },
        female: {
            oval: {
                current: "Naturally balanced brows with soft arch",
                suggestion: "Maintain the natural shape with a slight arch. Almost any brow shape complements an oval face well."
            },
            round: {
                current: "Soft, rounded natural brows",
                suggestion: "Create a higher, more defined arch to add length. Keep the tail pointed to lift and elongate the face."
            },
            square: {
                current: "Strong, defined brows",
                suggestion: "Soften with a curved arch. Avoid straight or angular brows that emphasize the jaw's squareness."
            },
            oblong: {
                current: "Naturally straight or low-arched brows",
                suggestion: "Keep brows flat and fuller to add width. A straighter brow minimizes face length."
            },
            heart: {
                current: "Naturally curved brows",
                suggestion: "A soft, rounded arch works best. Avoid overly thin or high arches that emphasize forehead width."
            }
        }
    },

    // ==================== MAKEUP RECOMMENDATIONS ====================
    makeup: {
        male: {
            base: "Light tinted moisturizer or concealer for evening skin tone. Keep it natural and minimal.",
            eyes: "Groomed eyebrows. Optional: clear brow gel for definition.",
            blush: "Generally not recommended for masculine looks, but a subtle bronzer on cheekbones can add warmth.",
            lips: "Lip balm for hydration. Keep lips natural."
        },
        female: {
            oval: {
                base: "Medium coverage foundation. Contour is optional as features are already balanced.",
                eyes: "Versatile - can try bold or subtle looks. Neutral shades for everyday, dramatic for evening.",
                blush: "Apply to the apples of cheeks. Any shade works well with oval faces.",
                lips: "Any lip color suits oval faces. Bold lips won't overwhelm features."
            },
            round: {
                base: "Use contour on sides of face and temples to add definition. Highlight on center of forehead, nose, and chin.",
                eyes: "Winged eyeliner to elongate eyes. Focus on upper lash line.",
                blush: "Apply diagonally from apples to temples to lift and slim.",
                lips: "Darker lip colors can help slim the face. Avoid overly glossy or plumping products."
            },
            square: {
                base: "Contour the corners of forehead and jawline to soften angles. Highlight cheekbones and center face.",
                eyes: "Soft, rounded eyeshadow shapes. Avoid harsh lines.",
                blush: "Apply in circular motions on apples of cheeks to soften face.",
                lips: "Rounded lip shapes. Use lip liner to create softer curves."
            },
            oblong: {
                base: "Contour horizontally under cheekbones and along hairline. Avoid vertical contouring. Highlight cheekbones.",
                eyes: "Focus on width rather than height. Extend eyeshadow slightly outward.",
                blush: "Apply horizontally across cheeks to add width.",
                lips: "Fuller lip looks can help balance face length. Use lip liner to enhance fullness."
            },
            heart: {
                base: "Contour temples and sides of forehead to minimize width. Highlight chin to add width at bottom of face.",
                eyes: "Soft, neutral shades. Avoid heavy eye makeup that draws attention to forehead width.",
                blush: "Apply below cheekbones in an upward angle toward temples.",
                lips: "Draw attention to lips with bold colors to balance forehead. Fuller lip looks work well."
            }
        }
    },

    // ==================== FACIAL PROPORTIONS DESCRIPTIONS ====================
    proportions: {
        oval: {
            forehead: "Proportionate width with gently rounded hairline. Well-balanced with other features.",
            eyes: "Evenly spaced with good balance. Ideally positioned in the middle third of the face.",
            nose: "Well-proportioned to face width. Straight alignment with balanced profile.",
            chin: "Gently rounded and proportionate to the jawline. Creates harmonious balance."
        },
        round: {
            forehead: "Wider and rounded at hairline. Creates soft, circular impression.",
            eyes: "May appear wider set. Balanced horizontally across face.",
            nose: "Proportionate width. May appear slightly wider due to face fullness.",
            chin: "Soft and rounded. Less prominent jawline definition."
        },
        square: {
            forehead: "Wide and angular at hairline. Creates strong horizontal line.",
            eyes: "Well-spaced. Strong bone structure around eye area.",
            nose: "Strong and defined. Complements angular features.",
            chin: "Strong, angular jawline and chin. Prominent and well-defined."
        },
        oblong: {
            forehead: "Longer from hairline to brow. Proportionate width but adds to face length.",
            eyes: "Well-positioned. May appear closer together relative to face length.",
            nose: "Longer profile. Proportionate to face length.",
            chin: "Extended from nose to chin point. Narrow jawline."
        },
        heart: {
            forehead: "Wider at temples. Prominent forehead creates top-heavy appearance.",
            eyes: "Well-spaced, often wide-set. Emphasized by broad forehead.",
            nose: "Tapers with face shape. Medium width.",
            chin: "Pointed or narrow. Creates distinctive heart shape with wider forehead."
        }
    },

    // ==================== FACE SHAPE DESCRIPTIONS ====================
    descriptions: {
        oval: "The face appears to have balanced proportions with a slightly rounded jawline and forehead, characteristic of an oval face shape. This is considered the most versatile face shape.",
        round: "The face has fuller cheeks with soft, circular features. The width and length are approximately equal, with minimal angles and a rounded jawline.",
        square: "The face features a strong, angular jawline with a broad forehead. The face width is similar to its length, with minimal tapering.",
        oblong: "The face is longer than it is wide, with a straight cheek line and minimal curve. The forehead, cheekbones, and jawline are similar in width.",
        heart: "The face is wider at the forehead and cheekbones, tapering to a narrower, more pointed chin, creating a heart-like shape."
    }
};

// Helper function to get recommendations
function getRecommendations(gender, faceShape) {
    const normalizedGender = gender.toLowerCase();
    const normalizedShape = faceShape.toLowerCase();
    
    return {
        hairstyle: RECOMMENDATIONS.hairstyles[normalizedGender]?.[normalizedShape] || {
            recommended: ["Consult with a professional stylist"],
            avoid: []
        },
        eyebrows: RECOMMENDATIONS.eyebrows[normalizedGender]?.[normalizedShape] || {
            current: "Natural brow shape",
            suggestion: "Maintain natural shape with light grooming"
        },
        makeup: normalizedGender === 'female' 
            ? RECOMMENDATIONS.makeup.female[normalizedShape]
            : RECOMMENDATIONS.makeup.male,
        proportions: RECOMMENDATIONS.proportions[normalizedShape] || {
            forehead: "Proportionate to face",
            eyes: "Well-balanced",
            nose: "Proportionate",
            chin: "Well-defined"
        },
        description: RECOMMENDATIONS.descriptions[normalizedShape] || "Face shape analysis complete."
    };
}