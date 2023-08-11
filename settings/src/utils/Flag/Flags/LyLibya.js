import * as React from "react";
const SvgLyLibya = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="LY_-_Libya_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#LY_-_Libya_svg__a)">
      <path fill="#55BA07" d="M0 9h16v3H0V9Z" />
      <path fill="#1D1D1D" d="M0 3h16v6H0V3Z" />
      <path fill="#E11C1B" d="M0 0h16v3H0V0Z" />
      <path
        fill="#fff"
        d="M7.899 7.533c-.896-.185-1.124-.72-1.115-1.444 0-.765.399-1.473 1.106-1.558.707-.085 1.285.18 1.589.547-.254-.999-1.095-1.108-1.74-1.108-.972-.008-1.998.664-1.998 2.194 0 1.369.908 2.046 2.026 2.068 1.398 0 1.616-.965 1.657-1.294a2.492 2.492 0 0 0-.234.2c-.28.258-.585.541-1.291.395Zm2.209-1.926-.638.247.662.292-.024.775.502-.535.729.15-.442-.593.386-.571-.61.128-.43-.486-.135.593Z"
      />
    </g>
  </svg>
);
export default SvgLyLibya;
