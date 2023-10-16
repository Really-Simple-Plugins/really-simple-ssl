import * as React from "react";
const SvgMkNorthMacedonia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MK_-_North_Macedonia_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#MK_-_North_Macedonia_svg__a)">
      <path
        fill="#F50100"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="MK_-_North_Macedonia_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g fill="#FFD018" mask="url(#MK_-_North_Macedonia_svg__b)">
        <path
          fillRule="evenodd"
          d="M0-.021v2.042l7 2.976L1.628-.021H0ZM8 6l1.5-6h-3L8 6Zm0 0-1.5 6h3L8 6ZM0 9.976v2.042h1.628L7 7 0 9.976Zm16-7.943V-.009h-1.628L9 5.01l7-2.976Zm0 9.997V9.988L9 7.012l5.372 5.018H16Zm0-7.53L10 6l6 1.5v-3ZM6 6 0 4.5v3L6 6Z"
          clipRule="evenodd"
        />
        <path
          stroke="#F50100"
          d="M8 8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgMkNorthMacedonia;
