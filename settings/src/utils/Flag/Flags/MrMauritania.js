import * as React from "react";
const SvgMrMauritania = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MR_-_Mauritania_svg__a"
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
    <g mask="url(#MR_-_Mauritania_svg__a)">
      <path
        fill="#1C7B4D"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="MR_-_Mauritania_svg__b"
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
      <g
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#MR_-_Mauritania_svg__b)"
      >
        <path fill="#E31D1C" d="M0 0v3h16V0H0ZM0 9v3h16V9H0Z" />
        <path
          fill="#FECA00"
          d="M8.121 7.19c2.593.014 3.323-2.157 3.323-2.157C11.297 6.89 10.27 8.15 8.12 8.15S5.183 6.518 4.798 4.89c0 0 .73 2.285 3.323 2.3Z"
        />
        <path
          fill="#FECA00"
          d="m8.819 4.907.168.982-.882-.464-.881.464.168-.982-.713-.767h.985l.441-.965.441.965h.986l-.713.767Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgMrMauritania;
