import * as React from "react";
const SvgSoSomalia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SO_-_Somalia_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#SO_-_Somalia_svg__a)">
      <path fill="#56C6F5" d="M0 0h16v12H0V0Z" />
      <path
        fill="#F7FCFF"
        d="M7.99 7.359 6.106 8.555l.632-2.094-1.343-1.369 1.85-.04.82-2.068.746 2.095 1.846.032-1.387 1.394.647 1.992L7.99 7.36Z"
      />
    </g>
  </svg>
);
export default SvgSoSomalia;
