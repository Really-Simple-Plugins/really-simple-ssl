/** @typedef {{children: string} & import('react').ComponentPropsWithoutRef<'div'>} RawHTMLProps */
/**
 * Component used as equivalent of Fragment with unescaped HTML, in cases where
 * it is desirable to render dangerous HTML without needing a wrapper element.
 * To preserve additional props, a `div` wrapper _will_ be created if any props
 * aside from `children` are passed.
 *
 * @param {RawHTMLProps} props Children should be a string of HTML or an array
 *                             of strings. Other props will be passed through
 *                             to the div wrapper.
 *
 * @return {JSX.Element} Dangerously-rendering component.
 */
export default function RawHTML({ children, ...props }: RawHTMLProps): JSX.Element;
export type RawHTMLProps = {
    children: string;
} & import('react').ComponentPropsWithoutRef<'div'>;
//# sourceMappingURL=raw-html.d.ts.map